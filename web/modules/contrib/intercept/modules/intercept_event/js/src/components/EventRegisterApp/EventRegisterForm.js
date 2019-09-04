// React
import React from 'react';
import PropTypes from 'prop-types';

// Redux
import { connect } from 'react-redux';

// UUID
import v4 from 'uuid/v4';

// Lodash
import get from 'lodash/get';
import map from 'lodash/map';

// Intercept
import interceptClient from 'interceptClient';
import drupalSettings from 'drupalSettings';

// Components
import Button from '@material-ui/core/Button';

import InputIncrementer from 'intercept/Input/InputIncrementer';

import Formsy, { addValidationRule } from 'formsy-react';
import EventRegisterConfirmation from './EventRegisterConfirmation';

const { actions, constants, select } = interceptClient;
const c = constants;

addValidationRule('isRequired', (values, value) => value !== '');
addValidationRule('isPositive', (values, value) => value >= 0);
addValidationRule('isPositiveTotal', values => values >= 0);

function FormWrapper(props) {
  return (
    <div className="form">
      <h2 className="form__heading">Number of Attendees?</h2>
      {props.children}
    </div>
  );
}

const text = {
  active: {
    button: 'Register',
    dialogHeading: 'Are you sure you want to register?',
  },
  waitlist: {
    button: 'Join Waitlist',
    dialogHeading: 'Are you sure you want to join the waitlist?',
  },
};

const buildEventRegistration = (values) => {
  const uuid = v4();

  const output = {
    id: uuid,
    type: c.TYPE_EVENT_REGISTRATION,
    attributes: {
      uuid,
      status: values.status,
    },
    relationships: {
      field_event: {
        data: {
          type: c.TYPE_EVENT,
          id: values.event,
        },
      },
      field_registrants: {
        data: map(values.registrants, (value, id) => ({
          type: c.TYPE_POPULATION_SEGMENT,
          id,
          meta: {
            count: value,
          },
        })),
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

class EventRegisterForm extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      openDialog: false,
      canSubmit: false,
      values: {},
      validationErrors: {},
      uuid: null,
    };

    this.form = React.createRef();

    this.disableButton = this.disableButton.bind(this);
    this.enableButton = this.enableButton.bind(this);
    this.getCurrentValues = this.getCurrentValues.bind(this);
    this.getValuesTotal = this.getValuesTotal.bind(this);
    this.saveEntitytoStore = this.saveEntitytoStore.bind(this);
    // this.onCloseDialog = this.onCloseDialog.bind(this);
    this.onInputChange = this.onInputChange.bind(this);
    // this.onOpenDialog = this.onOpenDialog.bind(this);
    this.onValueChange = this.onValueChange.bind(this);
    this.updateValue = this.updateValue.bind(this);
    this.updateValues = this.updateValues.bind(this);
    this.validateForm = this.validateForm.bind(this);
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

  getCurrentValues() {
    return this.form.current ? this.form.current.getModel() : this.props.values;
  }

  getValuesTotal() {
    const values = this.getCurrentValues();
    return this.props.segments.reduce((total, s) => total + (values[s.key] || 0), 0);
  }

  getCapacity() {
    return get(this, 'props.event.data.attributes.field_capacity_max') || 0;
  }

  getRegistrationCount() {
    return get(this, 'props.event.data.attributes.registration.total');
  }

  getAvailableCapacity() {
    return this.getCapacity() - this.getRegistrationCount();
  }

  getWaitlistCapacity() {
    return get(this, 'props.event.data.attributes.field_waitlist_max') || 0;
  }

  getWaitlistRegistrationCount() {
    return get(this, 'props.event.data.attributes.registration.total_waitlist');
  }

  getAvailableWaitlistCapacity() {
    return this.getWaitlistCapacity() - this.getWaitlistRegistrationCount();
  }

  getAvailableText() {
    // Assume there is unlimited capacity.
    if (this.getCapacity() === 0) {
      return '';
    }

    const availableCapacity = this.getAvailableCapacity();

    switch (availableCapacity) {
      case 0:
        return 'This event is full.';
      case 1:
        return `There is ${availableCapacity} seat available.`;
      default:
        return `There are ${availableCapacity} seats available.`;
    }
  }

  getWaitlistAvailableText() {
    // Assume there is unlimited capacity.
    if (this.getWaitlistCapacity() === 0) {
      return '';
    }

    const availableCapacity = this.getAvailableWaitlistCapacity();

    switch (availableCapacity) {
      case 0:
        return 'The waitlist is full.';
      case 1:
        return `There is only ${availableCapacity} seat available on the waitlist.`;
      default:
        return `There are only ${availableCapacity} seats available on the waitlist.`;
    }
  }

  getStatusText() {
    const total = this.getValuesTotal();
    // Must meet the minimum requirements.
    if (total <= 0) {
      return 'You must register at least 1 attendee';
    }

    // Must not exceed total capacity.
    if (this.isOverTotalCapacity(total)) {
      return `This event has a total capacity of ${this.getCapacity()}`;
    }

    // Must not exceed waitlist capacity.
    if (this.isOverCapacity(total) && this.hasWaitlist() && this.isOverWaitlistCapacity(total)) {
      return `${this.getAvailableText()} ${this.getWaitlistAvailableText()}`;
    }

    // Must not exceed total capacity.
    if (this.isOverCapacity(total) && this.hasWaitlist()) {
      return `${this.getAvailableText()} Would you like to join the waitlist?`;
    }

    return this.getAvailableText();
  }

  hasWaitlist() {
    return get(this, 'props.event.data.attributes.field_has_waitlist');
  }

  isDisabled(total) {
    return (
      !this.state.canSubmit ||
      total <= 0 ||
      this.isOverTotalCapacity(total) ||
      (this.isOverCapacity(total) && !this.hasWaitlist()) ||
      (this.isOverCapacity(total) && this.isOverWaitlistCapacity(total))
    );
  }

  isOverCapacity(total) {
    return this.getCapacity() !== 0 && (this.getAvailableCapacity() - total) < 0;
  }

  isOverTotalCapacity(total) {
    const capacity = this.getCapacity();

    if (capacity === 0) {
      return false;
    }

    return capacity > 0 && capacity - total < 0;
  }

  isOverWaitlistCapacity(total) {
    return this.getWaitlistCapacity() !== 0 && this.getAvailableWaitlistCapacity() - total < 0;
  }

  saveEntitytoStore = (values) => {
    const { save } = this.props;
    const entity = buildEventRegistration(values);
    this.setState({
      uuid: entity.id,
    });
    save(entity);
    return entity.id;
  };

  disableButton() {
    this.setState({ canSubmit: false });
  }

  enableButton() {
    this.setState({ canSubmit: true });
  }

  updateValue(key, value) {
    const values = { ...this.props.values, [key]: value };
    this.setState({ values });
  }

  updateValues(value) {
    const values = { ...this.props.values, ...value };
    this.setState({ values });
  }

  validateForm(values) {
    if (this.getValuesTotal(values) <= 0) {
      this.setState({
        validationErrors: {
          [this.props.segments[0].key]: 'You must register at least one person',
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
      values,
      segments,
      user,
      eventId,
      status,
    } = this.props;
    const { uuid } = this.state;
    const total = this.getValuesTotal();
    let currentStatus = status;
    const statusText = this.getStatusText();

    if (
      status === 'active' &&
      this.hasWaitlist() &&
      this.isOverCapacity(total) &&
      !this.isOverTotalCapacity(total) &&
      !this.isOverWaitlistCapacity(total)
    ) {
      currentStatus = 'waitlist';
    }

    if (segments.length <= 0) {
      return (
        <FormWrapper>
          <p>Loading segments</p>
        </FormWrapper>
      );
    }

    return (
      <FormWrapper>
        <Formsy
          className="form__main"
          ref={this.form}
          onChange={this.validateForm}
          onValidSubmit={this.onOpenDialog}
          onValid={this.enableButton}
          onInvalid={this.disableButton}
          validationErrors={this.state.validationErrors}
        >
          <div className="l--subsection input-group--find-room">
            {segments.map(s => (
              <InputIncrementer
                label={s.value}
                value={values[s.key] || 0}
                onChange={this.onValueChange(s.key)}
                key={s.key}
                name={s.key}
                min={0}
                int
                required={values.meeting}
                validations="isPositive"
                validationError="Attendees must be a positive number"
              />
            ))}
            <p>Total {total}</p>
          </div>

          <div className="form__actions">
            <Button
              variant="raised"
              size="small"
              color="primary"
              type="submit"
              className="button button--primary"
              disabled={this.isDisabled(total)}
            >
              {text[currentStatus].button}
            </Button>
            {statusText && (
              <p className="action-button__message action-button__message--left">{statusText}</p>
            )}
          </div>
        </Formsy>
        <EventRegisterConfirmation
          open={this.state.openDialog}
          onCancel={this.onCloseDialog}
          uuid={uuid}
          eventId={eventId}
          heading={text[currentStatus].dialogHeading}
          total={total}
          status={currentStatus}
          onConfirm={() =>
            this.saveEntitytoStore({
              user: user.uuid,
              event: eventId,
              status: currentStatus,
              registrants: this.getCurrentValues(),
            })
          }
        />
      </FormWrapper>
    );
  }
}

EventRegisterForm.propTypes = {
  segments: PropTypes.array,
  values: PropTypes.shape({}),
  user: PropTypes.object,
  eventId: PropTypes.string.isRequired,
  save: PropTypes.func.isRequired,
  status: PropTypes.string,
};

EventRegisterForm.defaultProps = {
  segments: [],
  values: {},
  user: {},
  status: 'active',
};

const mapStateToProps = (state, ownProps) => {
  const event = select.record(select.getIdentifier(c.TYPE_EVENT, ownProps.eventId))(state);
  const registrationStatus = get(event, 'data.attributes.registration.status');
  const status = registrationStatus === 'waitlist' ? 'waitlist' : 'active';

  return { status };
};

const mapDispatchToProps = dispatch => ({
  save: (data) => {
    dispatch(actions.add(data, c.TYPE_EVENT_REGISTRATION, data.id));
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(EventRegisterForm);
