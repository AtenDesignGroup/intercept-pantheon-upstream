import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import interceptClient from 'interceptClient';
import DialogConfirm from 'intercept/Dialog/DialogConfirm';
import get from 'lodash/get';

import RoomReservationStatus from './RoomReservationStatus';

const { constants, select } = interceptClient;
const c = constants;

// State constants
const IDLE = 'idle';
const CONFLICT = 'conflict';
const SAVED = 'saved';
const ERROR = 'error';
const LOADING = 'loading';

class ReserveRoomConfirmation extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      uuid: null,
      state: 'idle',
      saved: false,
      disableBackdropClick: true,
      disableEscapeKeyDown: true,
    };
  }

  handleConfirm = () => {
    this.props.onConfirm();
  }

  render() {
    const { open, onCancel, eventNid, state, uuid } = this.props;
    const { disableBackdropClick, disableEscapeKeyDown } = this.state;

    let content = null;
    let dialogProps = {
      onCancel,
    };

    switch (state) {
      case IDLE:
        dialogProps = {
          ...dialogProps,
          confirmText: 'Submit',
          cancelText: 'Cancel',
          heading: 'Confirm reservation request?',
          onConfirm: () => {
            this.props.onConfirm();
          },
        };
        break;
      case LOADING:
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
          onConfirm: this.handleConfirm,
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
        disableEscapeKeyDown={disableEscapeKeyDown}
        disableBackdropClick={disableBackdropClick}
      >
        {content}
      </DialogConfirm>
    );
  }
}

ReserveRoomConfirmation.propTypes = {
  onConfirm: PropTypes.func,
  onCancel: PropTypes.func,
  open: PropTypes.bool,
  state: PropTypes.string,
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
  state: 'idle',
};

const mapStateToProps = (state, ownProps) => {
  const { values } = ownProps;
  const eventId = values[c.TYPE_EVENT];
  let eventNid = null;

  if (eventId) {
    eventNid = get(select.event(values[c.TYPE_EVENT])(state), 'data.attributes.nid');
  }

  return {
    eventNid,
  };
};

export default connect(
  mapStateToProps,
)(ReserveRoomConfirmation);
