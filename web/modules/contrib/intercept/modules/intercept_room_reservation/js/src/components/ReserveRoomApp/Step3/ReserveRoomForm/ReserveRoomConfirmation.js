import React, { useState, useCallback } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import interceptClient from 'interceptClient';
import DialogConfirm from 'intercept/Dialog/DialogConfirm';
import get from 'lodash/get';

import RoomReservationStatus from './RoomReservationStatus';
import RoomReservationSummary from './RoomReservationSummary';
import withAvailability from './../../withAvailability';

const { api, constants, select, session } = interceptClient;
const c = constants;

// State constants
const IDLE = 'idle';
const CONFLICT = 'conflict';
const SAVED = 'saved';
const ERROR = 'error';
const LOADING = 'loading';

const ReserveRoomConfirmation = ({
  fetchAvailability,
  availabilityQuery,
  hasSuccessfullySaved,
  onConfirm,
  save,
  open,
  onCancel,
  values,
  eventNid,
}) => {
  const [entityState, setEntityState] = useState(IDLE);
  const [uuid, setUuid] = useState(null);

  // Redirect to reservations list if the reservation has been saved.
  if (hasSuccessfullySaved(uuid)) {
    const destination = interceptClient.utils.userIsStaff()
      ? '/manage/room-reservations/list'
      : '/account/room-reservations';
    window.location.href = destination;
  }

  const checkAvailability = useCallback(() => {
    const entityUuid = availabilityQuery.rooms[0];

    setEntityState(LOADING);

    return new Promise((resolve, reject) => {
      try {
        fetchAvailability(availabilityQuery, (r) => {
          const res = JSON.parse(r);
          if (res[entityUuid].has_reservation_conflict) {
            reject();
            setEntityState(CONFLICT);
          }
          else {
            resolve();
          }
        });
      }
      catch (error) {
        reject(error);
        setEntityState(ERROR);
      }
    });
  }, [fetchAvailability, availabilityQuery]);

  const handleConfirm = useCallback(() => {
    checkAvailability()
      .then(() => {
        const entityUuid = onConfirm();
        save(entityUuid);
        setUuid(entityUuid);
        setEntityState(SAVED);
      })
      .catch(() => {
        setEntityState(CONFLICT);
      });
  }, [checkAvailability, onConfirm, save]);

  let content = null;
  let dialogProps = {
    onCancel,
  };

  switch (entityState) {
    case IDLE:
      content = <RoomReservationSummary {...values} />;
      dialogProps = {
        ...dialogProps,
        confirmText: 'Submit',
        cancelText: 'Cancel',
        heading: 'Confirm reservation request?',
        onConfirm: handleConfirm,
      };
      break;
    case LOADING:
      content = <RoomReservationSummary {...values} />;
      dialogProps = {
        ...dialogProps,
        cancelText: 'Cancel',
        heading: 'Sending reservation request',
        onConfirm: null,
        onCancel: null,
      };
      break;
    case CONFLICT:
      dialogProps = {
        ...dialogProps,
        heading: 'This reservation time is no longer available.',
        cancelText: 'Close',
      };
      break;
    case ERROR:
      dialogProps = {
        ...dialogProps,
        heading: 'There was an unexpected error with your reservation.',
        cancelText: 'Close',
        confirmText: 'Try Again',
        onConfirm: handleConfirm,
      };
      break;
    case SAVED:
      content = <RoomReservationStatus uuid={uuid} />;
      dialogProps = {
        ...dialogProps,
        confirmText: 'View Your Reservations',
        cancelText: 'Close',
        heading: '',
        onConfirm: () => {
          window.location.href = '/account/room-reservations';
        },
      };

      // Handle event room reservations.
      if (eventNid) {
        dialogProps.confirmText = 'Back to Edit Event';
        dialogProps.onConfirm = () => {
          window.location.href = `/node/${eventNid}/edit`;
        };
      }
      break;
    default:
      break;
  }

  return (
    <DialogConfirm
      {...dialogProps}
      open={open}
      onBackdropClick={null}
      disableEscapeKeyDown={true}
      disableBackdropClick={true}
    >
      {content}
    </DialogConfirm>
  );
};

ReserveRoomConfirmation.propTypes = {
  availabilityQuery: PropTypes.object.isRequired,
  fetchAvailability: PropTypes.func.isRequired,
  onConfirm: PropTypes.func,
  onCancel: PropTypes.func,
  open: PropTypes.bool,
  save: PropTypes.func.isRequired,
  hasSuccessfullySaved: PropTypes.func.isRequired,
  values: PropTypes.object.isRequired,
  eventNid: PropTypes.number,
};

ReserveRoomConfirmation.defaultProps = {
  onConfirm: null,
  onCancel: null,
  open: false,
  eventNid: null,
  disableEscapeKeyDown: true,
  disableBackdropClick: true,
};

const mapStateToProps = (state, ownProps) => {
  const { values } = ownProps;
  const eventId = values[c.TYPE_EVENT];
  let eventNid = null;

  if (eventId) {
    eventNid = get(select.event(values[c.TYPE_EVENT])(state), 'data.attributes.nid');
  }

  // A Reservation saved successfully if it has a valid
  // drupal_internal__id.
  const hasSuccessfullySaved = (uuid) => {
    if (!uuid) {
      return false;
    }
    return !!get(select.roomReservation(uuid)(state), 'data.attributes.drupal_internal__id');
  };

  return {
    eventNid,
    hasSuccessfullySaved,
  };
};

const mapDispatchToProps = dispatch => ({
  save: (uuid) => {
    session
      .getToken()
      .then((token) => {
        dispatch(
          api[c.TYPE_ROOM_RESERVATION].sync(uuid, {
            headers: { 'X-CSRF-Token': token },
          }),
        );
      })
      .catch((e) => {
        console.log('Unable to save Reservation', e);
      });
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(withAvailability(ReserveRoomConfirmation));
