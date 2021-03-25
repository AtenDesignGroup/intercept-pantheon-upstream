// React
import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import moment from 'moment';

// Redux
import { connect } from 'react-redux';

// Lodash
import filter from 'lodash/filter';
import get from 'lodash/get';
import map from 'lodash/map';

// Intercept
import interceptClient from 'interceptClient';
import drupalSettings from 'drupalSettings';

import CloseIcon from '@material-ui/icons/Close';
import { AppBar, Button, Dialog, DialogContent, DialogContentText, DialogTitle, Toolbar, IconButton, Typography, Slide } from '@material-ui/core';

// Intercept Components
import SelectResource from 'intercept/SelectResource';
import SelectTime from 'intercept/Select/SelectTime';
import SelectUser from 'intercept/SelectUser';
import InputDate from 'intercept/Input/InputDate';
import InputNumber from 'intercept/Input/InputNumber';
import InputText from 'intercept/Input/InputText';
import RadioGroup from 'intercept/RadioGroup/RadioGroup';
import InputCheckbox from 'intercept/Input/InputCheckbox';

// Formsy
import Formsy, { addValidationRule } from 'formsy-react';

// Local Components
import withAvailability from './../ReserveRoomApp/withAvailability';
import ReserveRoomConfirmation from './ReserveRoomConfirmation';
import { isFutureTime, isLessThanMaxTime } from './../ReserveRoomApp/ReserveRoom';

const { actions, api, constants, select, session, utils } = interceptClient;
const c = constants;

// State constants
const CONFLICT = 'conflict';
const SAVED = 'saved';

const FIELD_REFRESHMENTS_DESC = 'I would like to serve refreshments and agree to the $25 charge that will be added to my library card. (Note: Some spaces may not allow refreshments. We will contact you if we are unable to fulfill this request.)';
const FIELD_REFRESHMENTS_OPTIONS = [
  {
    key: '1',
    value: 'Yes',
  },
  {
    key: '0',
    value: 'No',
  },
];

const FIELD_PUBLICIZE_DESC = get(drupalSettings, 'intercept.room_reservations.field_publicize.description', 'I would like to publicize this meeting');
const FIELD_PUBLICIZE_OPTIONS = [
  {
    key: '1',
    value: 'Yes',
  },
  {
    key: '0',
    value: 'No',
  },
];

const purposeRequiresExplanation = meetingPurpose =>
  meetingPurpose && meetingPurpose.data.attributes.field_requires_explanation;

addValidationRule('isRequired', (values, value) => value !== '');
addValidationRule('isPositive', (values, value) => value > 0);
addValidationRule(
  'isRequiredIfServingRefreshments',
  (values, value) => true,
);
addValidationRule('isRequiredIfMeeting', (values, value) => !values.meeting || value !== '');
addValidationRule('isGreaterOrEqualTo', (values, value, min) => min === null || value >= min);
addValidationRule('isLesserOrEqualTo', (values, value, max) => max === null || value <= max);
addValidationRule('isFutureDate', (values, value) =>
  !value || value >= utils.getUserStartOfDay(),
);
addValidationRule('isFutureTime', (values, value) => {
  if (value === null || values[c.DATE] === undefined) {
    return true;
  }
  return isFutureTime(value, values[c.DATE]);
});
addValidationRule('isAfterStart', (values, value) => (value === null || values.end > values.start));

addValidationRule('isAvailableDay', (values, value, roomAvailability) => ((utils.userIsStaff() && roomAvailability.is_closed) || !roomAvailability.is_closed));

addValidationRule('isAvailableDuration', (values, value, roomAvailability) => ((utils.userIsStaff() && roomAvailability.has_max_duration_conflict) || !roomAvailability.has_max_duration_conflict));

addValidationRule('isAvailableTime', (values, value, roomAvailability) => (
  !roomAvailability.has_conflict || (utils.userIsStaff && !roomAvailability.has_reservation_conflict)
));

const buildRoomReservation = (values, entityId) => {
  const output = {
    id: entityId,
    type: c.TYPE_ROOM_RESERVATION,
    attributes: {
      id: entityId,
      field_attendee_count: values.attendees,
      field_dates: {
        value: moment.tz(utils.getDateFromTime(values.start, values.date), utils.getUserTimezone())
          .format(),
        end_value: moment.tz(utils.getDateFromTime(values.end, values.date), utils.getUserTimezone())
          .format(),
      },
      field_group_name: values.groupName,
      field_meeting_purpose_details: values.meetingDetails,
      field_refreshments: values.refreshments === '1',
      field_refreshments_description: {
        value: values.refreshmentsDesc,
      },
      field_publicize: values.publicize === '1',
      field_status: 'requested',
      field_agreement: values.agreement,
    },
    relationships: {
      field_event: {
        data: values[c.TYPE_EVENT]
          ? {
            type: c.TYPE_EVENT,
            id: values[c.TYPE_EVENT],
          }
          : null,
      },
      field_room: {
        data: {
          type: c.TYPE_ROOM,
          id: values[c.TYPE_ROOM],
        },
      },
      field_meeting_purpose: {
        data: values.meetingPurpose
          ? {
            type: c.TYPE_MEETING_PURPOSE,
            id: values.meetingPurpose,
          }
          : null,
      },
      field_user: {
        data: {
          type: c.TYPE_USER,
          id: values.user,
        },
      },
    },
  };
  return output;
};

const Transition = React.forwardRef((props, ref) => <Slide direction="up" ref={ref} {...props} />);

const agreementText = get(drupalSettings, 'intercept.room_reservations.agreement_text', undefined);

class ReserveRoomEditForm extends PureComponent {
  constructor(props) {
    super(props);

    this.state = {
      expand: {
        refreshments: false,
        meeting: false,
        confirm: false,
        findRoom: false,
        findTime: false,
      },
      openAgreementDialog: false,
      openDialog: false,
      canSubmit: false,
      state: 'idle',
      uuid: null,
      availabilityShouldUpdate: true,
    };

    this.toggleState = this.toggleState.bind(this);
    this.updateValue = this.updateValue.bind(this);
    this.updateValues = this.updateValues.bind(this);
    this.toggleValue = this.toggleValue.bind(this);
    this.onCloseAgreementDialog = this.onCloseAgreementDialog.bind(this);
    this.onCloseDialog = this.onCloseDialog.bind(this);
    this.onInputChange = this.onInputChange.bind(this);
    this.onOpenAgreementDialog = this.onOpenAgreementDialog.bind(this);
    this.onOpenDialog = this.onOpenDialog.bind(this);
    this.onValueChange = this.onValueChange.bind(this);
    this.disableButton = this.disableButton.bind(this);
    this.enableButton = this.enableButton.bind(this);
  }

  componentDidUpdate() {
    this.checkAvailability();
  }

  onInputChange(key) {
    return (event) => {
      this.updateValue(key, event.target.value);
    };
  }

  onValueChange(key) {
    return (value) => {
      this.updateValue(key, value);
    };
  }

  onTimeChange(key) {
    return (value) => {
      this.setState({
        availabilityShouldUpdate: true,
      });
      this.updateValue(key, value);
    };
  }

  onValueUserChange(key) {
    return (value) => {
      this.updateValue(key, value);
    };
  }

  onOpenDialog = () => {
    this.setState({ openDialog: true });
  };

  onCloseDialog = () => {
    this.setState({ openDialog: false });
    this.props.closeEditDialog();
  };

  onOpenAgreementDialog = () => {
    this.setState({ openAgreementDialog: true });
  };

  onCloseAgreementDialog = () => {
    this.setState({ openAgreementDialog: false });
  };

  enableButton() {
    this.setState({ canSubmit: true });
  }

  toggleState(key) {
    return () => {
      this.setState({
        expand: {
          ...this.state.expand,
          [key]: !this.state.expand[key],
        },
      });
    };
  }

  expand(key) {
    return () => {
      this.setState({
        expand: {
          ...this.state.expand,
          [key]: true,
        },
      });
    };
  }

  collapse(key) {
    return () => {
      this.setState({
        expand: {
          ...this.state.expand,
          [key]: false,
        },
      });
    };
  }

  checkAvailability = () => {
    const { availabilityShouldUpdate } = this.state;
    const { availability, fetchAvailability, values } = this.props;

    if (availabilityShouldUpdate &&
      values.date !== null &&
      !availability.loading) {
      this.setState({
        availabilityShouldUpdate: false,
      });
      fetchAvailability(this.getDayAvailabilityQuery());
    }
  }

  saveEntitytoStore = (values) => {
    const { save, entityId } = this.props;
    const entity = buildRoomReservation(values, entityId);
    save(entity)
      .then(() => {
        this.setState({
          uuid: entityId,
          state: SAVED,
        });
      })
      .catch(() => {
        this.setState({ state: CONFLICT });
      });
    return entity.id;
  };

  updateValue(key, value) {
    const newValues = { ...this.props.values, [key]: value };
    this.props.onChange(newValues);
  }

  updateValues(value) {
    const newValues = { ...this.props.values, ...value };
    this.props.onChange(newValues);
  }

  toggleValue(key) {
    this.updateValue(key, !this.props.values[key]);
  }

  agreementLabel() {
    return (<React.Fragment>
      I agree to the <a
        onClick={this.onOpenAgreementDialog}
        role="link"
        tabIndex="0"
      >terms of service</a>.
      <Dialog
        open={this.state.openAgreementDialog}
        onCancel={this.onCloseAgreementDialog}
        onClose={this.onCloseAgreementDialog}
        TransitionComponent={Transition}
        className="dialog dialog--fullscreen"
      >
        <DialogTitle id="responsive-dialog-title">Terms of service</DialogTitle>
        <DialogContent>
          <DialogContentText>
            <span
              dangerouslySetInnerHTML={{ __html: agreementText }}
            />
          </DialogContentText>
        </DialogContent>
      </Dialog>
    </React.Fragment>
    );
  }

  getDisabledTimespans = () => {
    const { availability, room } = this.props;
    const roomAvailability = get(availability, `rooms.${room}.dates`);

    if (!roomAvailability) {
      return [];
    }

    return map(filter(roomAvailability, this.onlyTodaysReservations), item => ({
      start: utils.getTimeFromDate(utils.dateFromDrupal(item.start)),
      end: utils.getTimeFromDate(utils.dateFromDrupal(item.end)),
    }));
  };

  getDayAvailabilityQuery = () => {
    const { room, entityId, values } = this.props;
    const tz = utils.getUserTimezone();
    const date = moment.tz(values.date, tz);
    const start = date.clone().startOf('day');
    const end = date.clone().endOf('day');
    return {
      rooms: [room],
      start,
      end,
      exclude_uuid: [entityId],
    };
  };

  disableButton() {
    this.setState({ canSubmit: false });
  }

  isConflicted = () => {
    const { roomAvailability } = this.props;

    if (!roomAvailability.loading && roomAvailability) {
      return (!utils.userIsStaff() && (roomAvailability.has_max_duration_conflict
        || roomAvailability.has_open_hours_conflict
        || roomAvailability.has_reservation_conflict));
    }
    return true;
  }

  validateForm = () => {
    const {
      max,
      min,
      roomAvailability,
    } = this.props;

    const conflictProp = utils.userIsStaff()
      ? 'has_reservation_conflict'
      : 'has_conflict';
    let conflictMessage = 'Room is not available at this time';

    if (get(roomAvailability, conflictProp) || (!utils.userIsStaff() && isClosed) || (!utils.userIsStaff() && exceedsMaxDuration)) {
      this.setState({
        validationErrors: {
          start: conflictMessage,
          end: conflictMessage,
        },
      });
    }
    else {
      this.setState({
        validationErrors: {},
      });
    }
  }

  render() {
    const {
      closeEditDialog,
      combinedValues,
      event,
      meetingPurpose,
      min,
      max,
      room,
      roomAvailability,
      roomCapacity,
      step,
      values,
    } = this.props;
    const {
      state,
      uuid,
    } = this.state;
    const hasConflict = this.isConflicted();
    const disabledTimespans = this.getDisabledTimespans();
    const showMeetingPurposeExplanation = !!purposeRequiresExplanation(meetingPurpose);

    let content = null;
    let contact = null;

    this.form = React.createRef();

    // Contact Information display for customers
    if (drupalSettings.intercept.user.telephone && drupalSettings.intercept.user.email) {
      contact = (
        <div className="l--subsection">
          <h4 className="section-title section-title--secondary">Your Current Contact Information</h4>
          <small>
            Telephone: {drupalSettings.intercept.user.telephone}<br />
            Email: {drupalSettings.intercept.user.email}<br />
            <em>Need to update your info? After finishing your reservation visit My Account &gt; Profile.</em>
          </small>
        </div>
      );
    }
    content = (
      <Formsy
        className="form__main"
        ref={this.form}
        onValidSubmit={this.onOpenDialog}
        onValid={this.enableButton}
        onInvalid={this.disableButton}
        validationErrors={this.state.validationErrors}
      >
        <div className="l--2-col">
          <div className="l__main">
            <div className="l__primary">
              <div className="l--subsection">
                <h4 className="section-title section-title--secondary">Reservation Details</h4>
                <InputDate
                  handleChange={this.onTimeChange('date')}
                  value={values.date}
                  name="date"
                  required
                  clearable={false}
                  validations="isFutureDate"
                  validationErrors={{
                    isFutureDate: 'Date must be in the future',
                  }}
                />
                <SelectTime
                  clearable
                  label="Start Time"
                  value={values.start || null}
                  onChange={this.onTimeChange('start')}
                  name="start"
                  required
                  validations={{
                    isFutureTime: true,
                    isAvailableTime: roomAvailability,
                    isAvailableDuration: roomAvailability,
                  }}
                  validationErrors={{
                    isFutureTime: 'Must be in the future',
                    isAvailableTime: 'Room is not available',
                    isAvailableDuration: 'Reservation exceeds maximum duration',
                  }}
                  min={min}
                  max={max}
                  step={step}
                  disabledSpans={disabledTimespans}
                  disabledExclude={'trailing'}
                />
                <SelectTime
                  clearable
                  label="End Time"
                  value={values.end || null}
                  onChange={this.onTimeChange('end')}
                  name="end"
                  required
                  validations={{
                    isFutureTime: true,
                    isAfterStart: true,
                    isAvailableTime: roomAvailability,
                    isAvailableDuration: roomAvailability,
                  }}
                  validationErrors={{
                    isFutureTime: 'Must be in the future',
                    isAfterStart: 'Must be after start time',
                    isAvailableTime: 'Room is not available',
                    isAvailableDuration: 'Reservation exceeds maximum duration',
                  }}
                  min={min}
                  max={max}
                  step={step}
                  disabledSpans={disabledTimespans}
                  disabledExclude={'leading'}
                />
                <InputNumber
                  label="Number of Attendees"
                  value={values.attendees}
                  onChange={this.onValueChange('attendees')}
                  min={roomCapacity.min}
                  // Disable max because manually input of an out of range value has
                  // unexpected side effects with validation since the underlying input sets
                  // an out of range value to null.
                  // max={roomCapacity.max}
                  name={'attendees'}
                  int
                  required={!utils.userIsStaff()}
                  validations={{
                    isPositive: true,
                    isLesserOrEqualTo: roomCapacity.max,
                    isGreaterOrEqualTo: roomCapacity.min,
                  }}
                  validationErrors={{
                    isPositive: 'Attendees must be a positive number',
                    isLesserOrEqualTo: `The maximum capacity of this room is ${roomCapacity.max}`,
                    isGreaterOrEqualTo: `The minimum capacity of this room is ${
                      roomCapacity.min
                    }`,
                  }}
                  helperText={
                    roomCapacity.max &&
                    `This room holds ${roomCapacity.min} to ${roomCapacity.max} people`
                  }
                />
                <InputText
                  label="Group Name"
                  onChange={this.onValueChange('groupName')}
                  value={values.groupName}
                  name="groupName"
                  helperText={'Help others find you by name.'}
                  required={!utils.userIsStaff()}
                />
                <SelectResource
                  type={c.TYPE_MEETING_PURPOSE}
                  name="meetingPurpose"
                  handleChange={this.onInputChange('meetingPurpose')}
                  value={values.meetingPurpose}
                  label={'Purpose for using this room'}
                  required={!utils.userIsStaff()}
                />
                <InputText
                  label="Description"
                  onChange={this.onValueChange('meetingDetails')}
                  value={values.meetingDetails}
                  name="meetingDetails"
                  required={showMeetingPurposeExplanation}
                />
                {contact}
              </div>
            </div>
            <div className="l__secondary">
              <div className="l--subsection">
                <h4 className="section-title section-title--secondary">Account</h4>
                <SelectUser
                  label="Reserved For"
                  value={values.user}
                  onChange={value => this.onValueChange('user')(value.uuid)}
                  name={'user'}
                />
              </div>
              <div className="l--subsection">
                <h4 className="section-title section-title--secondary">Refreshments</h4>
                <RadioGroup
                  label={FIELD_REFRESHMENTS_DESC}
                  value={values.refreshments}
                  onChange={this.onValueChange('refreshments')}
                  name="refreshments"
                  required
                  options={FIELD_REFRESHMENTS_OPTIONS}
                />
                <InputText
                  label="Please describe your light refreshments."
                  value={values.refreshmentsDesc}
                  onChange={this.onValueChange('refreshmentsDesc')}
                  name="refreshmentDesc"
                  required={values.refreshments === '1'}
                  disabled={values.refreshments !== '1'}
                />
              </div>
              <div className="l--subsection">
                <h4 className="section-title section-title--secondary">Publicize</h4>
                <RadioGroup
                  label={FIELD_PUBLICIZE_DESC}
                  value={values.publicize}
                  onChange={this.onValueChange('publicize')}
                  name="publicize"
                  required
                  options={FIELD_PUBLICIZE_OPTIONS}
                />
              </div>
              {agreementText &&
                <div className="l--subsection">
                  <h4 className="section-title section-title--secondary">Terms of Service</h4>
                  <InputCheckbox
                    label={this.agreementLabel()}
                    checked={values.agreement}
                    onChange={() => this.toggleValue('agreement')}
                    required
                    value={values.agreement}
                    name="agreement"
                  />
                </div>
              }
              <Button
                size="large"
                color="secondary"
                className="button button--secondary"
                onClick={closeEditDialog}
              >Cancel</Button>
              <Button
                variant="contained"
                size="large"
                color="primary"
                type="submit"
                className="button button--primary"
                disabled={!this.state.canSubmit || hasConflict || !room || !values.agreement}
              >Save</Button>
            </div>
          </div>
        </div>
      </Formsy>
    );

    return (
      <div className="form">
        {content}
        <ReserveRoomConfirmation
          open={this.state.openDialog}
          onCancel={this.onCloseDialog}
          onConfirm={() =>
            this.saveEntitytoStore({
              ...combinedValues,
              [c.TYPE_ROOM]: room,
              [c.TYPE_EVENT]: event,
            })
          }
          state={state}
          uuid={uuid}
          values={{
            ...combinedValues,
            [c.TYPE_ROOM]: room,
            [c.TYPE_EVENT]: event,
          }}
        />

        <Dialog
          fullScreen
          open={this.state.expand.findRoom}
          onClose={() => {}}
          TransitionComponent={Transition}
          className="dialog dialog--fullscreen"
        >
          <AppBar className={'dialog__app-bar app-bar'}>
            <Toolbar>
              <IconButton color="inherit" onClick={this.collapse('findRoom')} aria-label="Close">
                <CloseIcon />
              </IconButton>
              <Typography variant="title" color="inherit" className={'app-bar_heading'}>
                Find a Room
              </Typography>
            </Toolbar>
          </AppBar>
        </Dialog>
      </div>
    );
  }
}

ReserveRoomEditForm.propTypes = {
  values: PropTypes.shape({
    agreement: PropTypes.bool,
    attendees: PropTypes.number,
    date: PropTypes.object,
    groupName: PropTypes.string,
    meetingDetails: PropTypes.string,
    meetingPurpose: PropTypes.string,
    refreshments: PropTypes.string,
    refreshmentsDesc: PropTypes.string,
    publicize: PropTypes.string,
    user: PropTypes.string,
    start: PropTypes.string,
    end: PropTypes.string,
  }),
  onChange: PropTypes.func.isRequired,
  save: PropTypes.func.isRequired,
  meetingPurpose: PropTypes.object,
  combinedValues: PropTypes.object,
  room: PropTypes.string,
  roomCapacity: PropTypes.shape({
    min: PropTypes.number,
    max: PropTypes.number,
  }).isRequired,
  event: PropTypes.string,
  entityId: PropTypes.string.isRequired,
  step: PropTypes.number,
  fetchAvailability: PropTypes.func.isRequired,
  availability: PropTypes.object.isRequired,
  roomAvailability: PropTypes.object,
};

ReserveRoomEditForm.defaultProps = {
  combinedValues: {},
  values: {
    agreement: true,
    attendees: null,
    groupName: '',
    meetingPurpose: '',
    meetingDetails: '',
    refreshments: '',
    refreshmentsDesc: '',
    publicize: '',
    user: drupalSettings.intercept.user.uuid,
  },
  meetingPurpose: null,
  room: null,
  event: null,
  step: 15,
  hasConflict: false,
  roomAvailability: {},
};

const mapStateToProps = (state, ownProps) => {
  const hours =
    ownProps.values.date.start && ownProps.room
      ? // Open hours for current day.
      select.roomLocationHours(
        ownProps.room,
        utils.getDayTimeStamp(ownProps.values.date.start),
      )(state)
      : // Default open hours.
      select.locationsOpenHoursLimit(state);
  return {
    hours,
    meetingPurpose: ownProps.values[c.TYPE_MEETING_PURPOSE]
      ? select.record(
        select.getIdentifier(c.TYPE_MEETING_PURPOSE, ownProps.values[c.TYPE_MEETING_PURPOSE]),
      )(state)
      : null,
    roomCapacity: ownProps.room
      ? select.roomCapacity(ownProps.room)(state)
      : {
        min: 0,
        max: null,
      },
    getRoomReservation: uuid => select.roomReservation(uuid)(state),
  };
};

const mapDispatchToProps = dispatch => ({
  save: (data) => {
    dispatch(actions.edit(data, c.TYPE_ROOM_RESERVATION, data.id));
    return Promise.resolve(session
      .getToken()
      .then((token) => {
        dispatch(api[c.TYPE_ROOM_RESERVATION].sync(data.id, { headers: { 'X-CSRF-Token': token } }));
      })
      .catch((e) => {
        console.log('Unable to save Reservation', e);
      }),
    );
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(withAvailability(ReserveRoomEditForm));
