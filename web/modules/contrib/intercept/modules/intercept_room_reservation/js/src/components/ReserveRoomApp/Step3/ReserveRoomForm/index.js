// React
import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import moment from 'moment';

// Redux
import { connect } from 'react-redux';

// Lodash
import get from 'lodash/get';

// Intercept
import interceptClient from 'interceptClient';
import drupalSettings from 'drupalSettings';

// UUID
import { v4 as uuidv4 } from 'uuid';

import CloseIcon from '@material-ui/icons/Close';
import { AppBar, Button, Dialog, DialogContent, DialogContentText, DialogTitle, Toolbar, IconButton, Typography, Slide } from '@material-ui/core';

// Intercept Components
import SelectResource from 'intercept/SelectResource';
import SelectUser from 'intercept/SelectUser';
import InputNumber from 'intercept/Input/InputNumber';
import InputText from 'intercept/Input/InputText';
import RadioGroup from 'intercept/RadioGroup/RadioGroup';
import InputCheckbox from 'intercept/Input/InputCheckbox';

// Formsy
import Formsy, { addValidationRule } from 'formsy-react';

// Local Components
import ReserveRoomConfirmation from './ReserveRoomConfirmation';
import ReservationTeaser from './../../../ReservationTeaser';

const { actions, constants, select, utils } = interceptClient;
const c = constants;

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

const buildRoomReservation = (values) => {
  const uuid = uuidv4();
  const output = {
    id: uuid,
    type: c.TYPE_ROOM_RESERVATION,
    attributes: {
      uuid,
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
        data: values[c.TYPE_MEETING_PURPOSE]
          ? {
            type: c.TYPE_MEETING_PURPOSE,
            id: values[c.TYPE_MEETING_PURPOSE],
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

class ReserveRoomForm extends PureComponent {
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
      uuid: null,
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

  componentDidUpdate(prevProps) {
    const { values } = this.props;
    const valuesChanged = prevProps.values.attendees !== values.attendees;

    if (valuesChanged) {
      this.forceUpdate();
    }
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

  onOpenDialog = () => {
    this.setState({ openDialog: true });
  };

  onCloseDialog = () => {
    this.setState({ openDialog: false });
  };

  onOpenAgreementDialog = () => {
    this.setState({ openAgreementDialog: true });
  };

  onCloseAgreementDialog = () => {
    this.setState({ openAgreementDialog: false });
  };

  disableButton() {
    this.setState({ canSubmit: false });
  }

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

  saveEntitytoStore = (values) => {
    const { save } = this.props;
    const entity = buildRoomReservation(values);
    this.setState({
      uuid: entity.id,
    });
    save(entity);
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

  render() {
    const {
      availabilityQuery,
      combinedValues,
      event,
      hasConflict,
      meetingPurpose,
      room,
      roomCapacity,
      values,
    } = this.props;
    const {
      uuid,
    } = this.state;
    const showMeetingPurposeExplanation = !!purposeRequiresExplanation(meetingPurpose);

    let content = null;
    let contact = null;

    this.form = React.createRef();

    // Show the reservation teaser if it has successfully saved.
    if (uuid && get(this.props.getRoomReservation(uuid), `${uuid}.state.saved`) === true) {
      content = <ReservationTeaser id={uuid} />;
    }
    else {
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
        >
          <div className="l--2-col">
            <div className="l__main">
              <div className="l__primary">
                <div className="l--subsection">
                  <h4 className="section-title section-title--secondary">Reservation Details</h4>
                  <div className="form-item">
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
                      required
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
                  </div>
                  <div className="form-item">
                    <InputText
                      label="Group Name"
                      onChange={this.onValueChange('groupName')}
                      value={values.groupName}
                      name="groupName"
                      helperText={'Help others find you by name.'}
                      required={!utils.userIsStaff()}
                    />
                  </div>
                  <div className="form-item">
                    <SelectResource
                      type={c.TYPE_MEETING_PURPOSE}
                      name={c.TYPE_MEETING_PURPOSE}
                      handleChange={this.onInputChange(c.TYPE_MEETING_PURPOSE)}
                      value={values.meetingPurpose}
                      label={'Purpose for using this room'}
                      required
                    />
                  </div>
                  <div className="form-item">
                    <InputText
                      label="Description"
                      onChange={this.onValueChange('meetingDetails')}
                      value={values.meetingDetails}
                      name="meetingDetails"
                      required={showMeetingPurposeExplanation}
                    />
                  </div>
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
                  <div className="form-item">
                    <InputText
                      label="Please describe your light refreshments."
                      value={values.refreshmentsDesc}
                      onChange={this.onValueChange('refreshmentsDesc')}
                      name="refreshmentDesc"
                      required={values.refreshments === '1'}
                      disabled={values.refreshments !== '1'}
                    />
                    </div>
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
                  variant="contained"
                  size="large"
                  color="primary"
                  type="submit"
                  className="button button--primary"
                  disabled={!this.state.canSubmit || hasConflict || !room || !values.agreement}
                >Next</Button>
              </div>
            </div>
          </div>
        </Formsy>
      );
    }

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
          values={{
            ...combinedValues,
            [c.TYPE_ROOM]: room,
            [c.TYPE_EVENT]: event,
          }}
          availabilityQuery={availabilityQuery}
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

ReserveRoomForm.propTypes = {
  availabilityQuery: PropTypes.object.isRequired,
  values: PropTypes.shape({
    agreement: PropTypes.bool,
    attendees: PropTypes.number,
    groupName: PropTypes.string,
    meetingDetails: PropTypes.string,
    [c.TYPE_MEETING_PURPOSE]: PropTypes.string,
    refreshments: PropTypes.string,
    refreshmentsDesc: PropTypes.string,
    publicize: PropTypes.string,
    user: PropTypes.string,
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
  hasConflict: PropTypes.bool,
};

ReserveRoomForm.defaultProps = {
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
  hasConflict: false,
};

const mapStateToProps = (state, ownProps) => ({
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
});

const mapDispatchToProps = dispatch => ({
  save: (data) => {
    dispatch(actions.add(data, c.TYPE_ROOM_RESERVATION, data.id));
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(ReserveRoomForm);
