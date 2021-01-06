import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import moment from 'moment';

// Lodash
import get from 'lodash/get';

// material-ui
import {
  Dialog,
  DialogContent,
  DialogTitle,
  TextField,
} from '@material-ui/core';

/* eslint-disable */
import interceptClient from 'interceptClient';
import drupalSettings from 'drupalSettings';

import ButtonReservationAction from 'intercept/ButtonReservationAction';
import DialogConfirm from 'intercept/Dialog/DialogConfirm';
import LoadingIndicator from 'intercept/LoadingIndicator';
import ReservationStatus from 'intercept/ReservationStatus';
/* eslint-enable */

import withAvailability from './../ReserveRoomApp/withAvailability';
import ReserveRoomEditForm from './../ReserveRoomEditForm';

const { actions, api, select, session, utils } = interceptClient;
const c = interceptClient.constants;

const getRecordValues = record => ({
  agreement: get(record, 'data.attributes.field_agreement'),
  attendees: get(record, 'data.attributes.field_attendee_count'),
  date: utils.dateFromDrupal(get(record, 'data.attributes.field_dates.value')),
  groupName: get(record, 'data.attributes.field_group_name') || '',
  meetingPurpose: get(record, 'data.relationships.field_meeting_purpose.data.id'),
  meetingDetails: get(record, 'data.attributes.field_meeting_purpose_details') || '',
  refreshments: get(record, 'data.attributes.field_refreshments') ? "1" : "0",
  refreshmentsDesc: get(record, 'data.attributes.field_refreshments_description.value') || '',
  [c.TYPE_ROOM]: get(record, 'data.relationships.field_room.data.id'),
  publicize: get(record, 'data.attributes.field_publicize') ? "1" : "0",
  start: utils.getTimeFromDate(utils.dateFromDrupal(get(record, 'data.attributes.field_dates.value'))),
  end: utils.getTimeFromDate(utils.dateFromDrupal(get(record, 'data.attributes.field_dates.end_value'))),
  user: drupalSettings.intercept.user.uuid,
});

class RoomReservationActionButtonApp extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      availabilityShouldUpdate: true,
      open: false,
      editDialogOpen: false,
      errorDialogOpen: false,
      disableBackdropClick: true,
      disableEscapeKeyDown: true,
      dialogProps: {},
      formValues: {
        [c.TYPE_ROOM]: props.room || null,
        date: null,
        start: null,
        end: null,
        agreement: utils.userIsStaff(),
        attendees: null,
        groupName: '',
        meeting: false,
        [c.TYPE_MEETING_PURPOSE]: null,
        meetingDetails: '',
        refreshmentsDesc: '',
        user: utils.getUserUuid(),
      },
      notes: '',
    };
    this.actionButton = this.actionButton.bind(this);
  }

  componentDidMount() {
    this.props.fetchReservation(this.props.entityId);
  }

  componentDidUpdate(prevProps) {
    const { record, isLoading } = this.props;
    const prevRecord = prevProps.record;
    const hasError = get(record, 'state.error');
    const prevHasError = get(prevRecord, 'state.error');

    if (!isLoading &&
      record !== null &&
      prevRecord !== record) {
      this.setFormValues(getRecordValues(record));
    }

    this.checkAvailability(prevProps.record);

    if (hasError && !prevHasError) {
      // Alert the user to the error
      // eslint-disable-next-line react/no-did-update-set-state
      this.setState({ errorDialogOpen: true });
    }
  }

  onlyTodaysReservations = (reservation) => {
    const { formValues } = this.state;
    const start = utils.dateFromDrupal(reservation.start);
    const eod = moment(get(formValues, 'date'))
      .tz(utils.getUserTimezone())
      .endOf('day');

    return start < eod;
  };

  // Get query params based on current rooms and values.
  getRoomAvailabilityQuery = () => {
    const { formValues } = this.state;
    const { record, entityId } = this.props;
    const room = get(record, 'data.relationships.field_room.data.id');
    return {
      rooms: [room],
      start: utils.getDateFromTime(formValues.start, formValues.date),
      end: utils.getDateFromTime(formValues.end, formValues.date),
      exclude_uuid: [entityId],
    };
  };

  getActions = (status) => {
    const { cancel, deny, approve, request, edit } = this;
    const { editDialogOpen } = this.state;
    const isManager = utils.userIsManager();

    switch (status) {
      case 'requested':
        return isManager ? [approve(), deny(), edit()] : [edit(), cancel()];
      case 'denied':
        return isManager ? [approve(), cancel()] : null;
      case 'approved':
        return isManager ? [deny(), edit(), cancel()] : [edit(), cancel()];
      case 'canceled':
        return (this.isConflicted() && !editDialogOpen) ? this.getConflictedMessage() : [request(), edit()];
      default:
        return null;
    }
  };

  getDialogProps = (status) => {
    switch (status) {
      case 'requested':
        return {
          status: 'requested',
          heading: 'Are you sure you want to rerequest this reservation?',
        };
      case 'denied':
        return {
          status: 'denied',
          heading: 'Are you sure you want to deny this reservation?',
        };
      case 'approved':
        return {
          status: 'approved',
          heading: 'Are you sure you want to approve this reservation?',
        };
      case 'canceled':
        return {
          status: 'canceled',
          heading: 'Are you sure you want to cancel this reservation?',
          children: this.cancellationNotesInput(),
        };
      default:
        return null;
    }
  };

  getConflictedMessage = () => {
    const { record, availability } = this.props;
    const room = get(record, 'data.relationships.field_room.data.id');
    if (!availability.loading && availability.rooms[room]) return (<p className="action-button__message">This reservation time is no longer available.</p>);
    return '';
  }

  getHours = () => {
    const { hours } = this.props;

    return hours
      ? {
        min: hours.min,
        max: (hours.max.endsWith('00') ? String(hours.max - 55) : String(hours.max - 15)),
      }
      : {
        min: null,
        max: null,
      };
  }

  setFormValues = (formValues) => {
    this.setState({
      formValues: {
        ...this.state.formValues,
        ...formValues,
      },
    });
  }

  checkAvailability = () => {
    const { availabilityShouldUpdate, formValues } = this.state;
    const { record, isLoading, availability, fetchAvailability } = this.props;

    if (!isLoading &&
      record !== null &&
      availabilityShouldUpdate &&
      formValues.date !== null &&
      formValues.start !== null &&
      formValues.end !== null &&
      (formValues.start !== formValues.end) &&
      !availability.loading) {
      this.setState({
        availabilityShouldUpdate: false,
      });
      fetchAvailability(this.getRoomAvailabilityQuery());
    }
  }

  isConflicted = () => {
    const { record, availability } = this.props;
    const room = get(record, 'data.relationships.field_room.data.id');

    if (!availability.loading && availability.rooms[room]) {
      const availabilityStatus = availability.rooms[room];
      return availabilityStatus.has_max_duration_conflict
        || availabilityStatus.has_open_hours_conflict
        || availabilityStatus.has_reservation_conflict;
    }
    return true;
  }

  openDialog = (status) => {
    this.setState({ open: true, dialogProps: this.getDialogProps(status) });
  }

  openEditDialog = () => {
    this.setState({ editDialogOpen: true });
  }

  closeDialog = () => {
    this.setState({ open: false });
  }

  closeEditDialog = () => {
    const { record } = this.props;
    this.setState({ editDialogOpen: false });
    this.setFormValues(getRecordValues(record));
  }

  closeErrorDialog = () => {
    this.props.fetchReservation(this.props.entityId);
    this.setState({ errorDialogOpen: false });
  }

  confirmDialog = (status, notes) => {
    this.props.setStatusTo(status, notes);
    this.closeDialog();
  }

  cancel = () => this.actionButton({
    status: 'canceled',
    label: 'Cancel',
  });

  deny = () => this.actionButton({
    status: 'denied',
    label: 'Deny',
  });

  request = () => this.actionButton({
    status: 'requested',
    label: 'Rerequest',
  });

  approve = () => this.actionButton({
    status: 'approved',
    label: 'Approve',
    variant: 'contained',
  });

  edit = () => this.editButton({
    label: 'Edit',
    variant: 'contained',
  });

  handleFormChange = (formValues) => {
    const { record } = this.props;

    this.setState({
      formValues: {
        ...getRecordValues(record),
        ...formValues,
      },
      availabilityShouldUpdate: true,
    });
  };

  handleNotesChange = (event) => {
    this.setState({ notes: event.target.value });
  }

  cancellationNotesInput = () => {
    const isManager = utils.userIsManager();
    if (isManager) {
      return (
        <TextField
          label="Cancellation Reason"
          type="text"
          id="notes"
          onChange={this.handleNotesChange}
          defaultValue={this.props.notes}
          className="input input--text"
          InputLabelProps={{
            className: 'input__label',
          }}
          inputProps={{}}
          fullWidth
        />
      );
    }
    return false;
  }

  actionButton({ status, label, variant }) {
    const { record, entityId } = this.props;

    return record ? (
      <ButtonReservationAction
        entityId={entityId}
        type={c.TYPE_ROOM_RESERVATION}
        record={record}
        text={label}
        onClick={() => this.openDialog(status)}
        key={status}
        variant={variant}
      />
    ) : null;
  }

  editButton({ label, variant }) {
    const { record, entityId } = this.props;

    return record ? (
      <ButtonReservationAction
        entityId={entityId}
        type={c.TYPE_ROOM_RESERVATION}
        record={record}
        text={label}
        onClick={() => this.openEditDialog()}
        key="edit"
        variant={variant}
      />
    ) : null;
  }


  dialog = () => {
    const { dialogProps, notes } = this.state;
    return (
      <DialogConfirm
        {...dialogProps}
        open={this.state.open}
        onClose={this.onDialogClose}
        onCancel={this.closeDialog}
        onBackdropClick={null}
        disableEscapeKeyDown={this.state.disableEscapeKeyDown}
        disableBackdropClick={this.state.disableBackdropClick}
        onConfirm={() => this.confirmDialog(dialogProps.status, notes)}
        confirmText="Yes"
      />
    );
  };

  editDialog = () => {
    const { record, entityId, availability } = this.props;
    const limits = utils.userIsStaff()
      ? {
        min: '0000',
        max: '2400',
      }
      : this.getHours();
    const dialogTitle = record ? `Edit Reservation ${get(record, 'data.attributes.location')}` : 'Edit Reservation';
    const room = get(record, 'data.relationships.field_room.data.id');

    return record ? (
      <Dialog
        open={this.state.editDialogOpen}
        onClose={this.closeEditDialog}
        aria-labelledby="responsive-dialog-title"
        onBackdropClick={this.closeEditDialog}
        maxWidth="md"
      >
        <DialogTitle id="responsive-dialog-title">{dialogTitle}</DialogTitle>
        <DialogContent>
          <ReserveRoomEditForm
            closeEditDialog={this.closeEditDialog}
            entityId={entityId}
            values={this.state.formValues}
            combinedValues={this.state.formValues}
            onChange={this.handleFormChange}
            min={limits.min}
            max={limits.max}
            room={room}
            event={get(record, 'data.relationships.field_event.data.id')}
            roomAvailability={get(availability, `rooms.${room}`)}
          />
        </DialogContent>
      </Dialog>
    ) : null;
  };

  errorDialog = () => {
    const { dialogProps } = this.state;
    const { record } = this.props;
    const errors = get(record, 'state.error.errors') || [];
    const text = errors.map(err => err.detail.replace('Entity is not valid: ', '')) || 'Unknown Error';

    return (
      <DialogConfirm
        {...dialogProps}
        open={this.state.errorDialogOpen}
        onClose={this.closeErrorDialog}
        onConfirm={this.closeErrorDialog}
        onBackdropClick={null}
        disableEscapeKeyDown={this.state.disableEscapeKeyDown}
        disableBackdropClick={this.state.disableBackdropClick}
        heading={'Unable to update reservation'}
        text={text}
        confirmText="Close"
      />
    );
  };

  render() {
    const { record, isLoading } = this.props;
    const status = get(record, 'data.attributes.field_status') || this.props.status;

    const buttons = isLoading ? (
      <LoadingIndicator loading={isLoading} size={20} />
    ) : (
      this.getActions(status)
    );

    return (
      <div className="reservation-register-button__inner">
        <ReservationStatus status={status} syncing={get(record, 'state.syncing')} />
        {buttons}
        {this.dialog()}
        {this.editDialog()}
        {this.errorDialog()}
      </div>
    );
  }
}

RoomReservationActionButtonApp.propTypes = {
  // Passed Props
  entityId: PropTypes.string.isRequired,
  type: PropTypes.string.isRequired,
  status: PropTypes.string.isRequired,
  // Connect
  fetchReservation: PropTypes.func.isRequired,
  fetchAvailability: PropTypes.func.isRequired,
  availability: PropTypes.object.isRequired,
  isLoading: PropTypes.bool,
  setStatusTo: PropTypes.func.isRequired,
  record: PropTypes.object,
  notes: PropTypes.string,
};

RoomReservationActionButtonApp.defaultProps = {
  record: null,
  isLoading: false,
  notes: null,
};

const mapStateToProps = (state, ownProps) => {
  const record = select.record(
    select.getIdentifier(c.TYPE_ROOM_RESERVATION, ownProps.entityId),
  )(state);
  const room = get(record, 'data.relationships.field_room.data.id');
  const formValues = getRecordValues(record);
  const hours =
    formValues && formValues.date && room
      ? // Open hours for current day.
      select.roomLocationHours(
        room,
        utils.getDayTimeStamp(formValues.date),
      )(state)
      : // Default open hours.
      select.locationsOpenHoursLimit(state);

  return {
    hours,
    room,
    rooms: select.roomsAscending(state),
    record,
    notes: get(record, 'data.attributes.notes'),
    isLoading:
      select.recordsAreLoading(c.TYPE_ROOM_RESERVATION)(state) ||
      select.recordIsLoading(c.TYPE_ROOM_RESERVATION, ownProps.entityId)(state),
  };
};

const mapDispatchToProps = (dispatch, ownProps) => {
  const uuid = ownProps.entityId;
  const { type } = ownProps;

  const save = () => {
    session
      .getToken()
      .then((token) => {
        dispatch(api[type].sync(uuid, { headers: { 'X-CSRF-Token': token } }));
      })
      .catch((e) => {
        console.log('Unable to save Reservation', e);
      });
  };

  const setStatus = (status, record, notes) => {
    const { data } = record;
    data.attributes.field_status = status;
    if (notes) {
      data.attributes.notes = notes;
    }
    dispatch(actions.edit(data, type, uuid));
  };

  return {
    setStatusTo: (status, record, notes) => {
      setStatus(status, record, notes);
      save(type);
    },
    fetchReservation: (id) => {
      dispatch(
        api[c.TYPE_ROOM_RESERVATION].fetchResource(id, {
          include: [
            'field_room',
            'field_room.field_location',
          ],
          fields: {
            [c.TYPE_ROOM]: ['field_capacity_max', 'field_capacity_min', 'field_location'],
          },
        }),
      );
    },
  };
};

function mergeProps(stateProps, dispatchProps, ownProps) {
  return {
    ...ownProps,
    ...stateProps,
    ...dispatchProps,
    setStatusTo: (status, notes) => dispatchProps.setStatusTo(status, stateProps.record, notes),
  };
}

export default connect(
  mapStateToProps,
  mapDispatchToProps,
  mergeProps,
)(withAvailability(RoomReservationActionButtonApp));
