import React from 'react';

import interceptClient from 'interceptClient';
import drupalSettings from 'drupalSettings';

// Lodash
import get from 'lodash/get';

import InputIncrementer from 'intercept/Input/InputIncrementer';

import Formsy, { addValidationRule } from 'formsy-react';

import { Button } from '@material-ui/core';

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
    button: 'Register as Guest',
  },
  waitlist: {
    button: 'Join Waitlist as Guest',
  },
};

class RegisterEventStep2 extends React.PureComponent {
  constructor(props) {
    super(props);

    this.state = {
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
    this.onInputChange = this.onInputChange.bind(this);
    this.onValueChange = this.onValueChange.bind(this);
    this.updateValue = this.updateValue.bind(this);
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

  getRegistrationLimit() {
    return get(this, 'props.event.data.attributes.field_event_user_reg_max') || 0;
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
    const totalCapacity = this.getCapacity();
    // Assume there is unlimited capacity.
    if (totalCapacity === 0) {
      return '';
    }

    const availableCapacity = this.getAvailableCapacity();

    switch (availableCapacity) {
      case 0:
        return 'This event is full.';
      case 1:
        return `There is ${availableCapacity} of ${totalCapacity} seat available.`;
      default:
        return `There are ${availableCapacity} of ${totalCapacity} seats available.`;
    }
  }

  getWaitlistAvailableText() {
    const waitlistCapacity = this.getWaitlistCapacity();

    // Assume there is unlimited capacity.
    if (waitlistCapacity === 0) {
      return '';
    }

    const availableCapacity = this.getAvailableWaitlistCapacity();

    switch (availableCapacity) {
      case 0:
        return 'The waitlist is full.';
      case 1:
        return `There is only ${availableCapacity} of ${waitlistCapacity} seats available on the waitlist.`;
      default:
        return `There are only ${availableCapacity} of ${waitlistCapacity} seats available on the waitlist.`;
    }
  }

  getLimitText() {
    const limit = this.getRegistrationLimit();
    // Must not exceed total registrations per user.

    switch(limit) {
      case 0: // no limit, return no message
        return null;
      case 1: // limit 1 registration per user
        return `Limit ${limit} registration per user`
      default: // limit (n) registrations per user
        return `Limit ${limit} registrations per user`
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
      this.isOverUserLimit(total) ||
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

  isOverUserLimit(total) {
    return this.getRegistrationLimit() !== 0 && (this.getRegistrationLimit() - total) < 0;
  }

  disableButton() {
    this.setState({ canSubmit: false });
  }

  enableButton() {
    this.setState({ canSubmit: true });
  }

  updateValue(key, value) {
    const values = { ...this.state.values, [key]: value };
    this.setState({ values });
    this.props.onChange({ registrants: values });
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
      status,
      onOpenDialog,
    } = this.props;
    const total = this.getValuesTotal();
    let currentStatus = status;
    const limitText = this.getLimitText();
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
          onValidSubmit={onOpenDialog}
          onValid={this.enableButton}
          onInvalid={this.disableButton}
          validationErrors={this.state.validationErrors}
        >
          {limitText && (
            <p className="action-button__message action-button__message--left">{limitText}</p>
          )}
          {statusText && (
            <p className="action-button__message action-button__message--left">{statusText}</p>
          )}
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
            <p>Total: {total}</p>
          </div>

          <div className="form__actions">
            <Button
              variant="contained"
              size="small"
              color="primary"
              type="submit"
              className="button button--primary"
              disabled={this.isDisabled(total)}
            >
              {text[currentStatus].button}
            </Button>
          </div>
        </Formsy>
      </FormWrapper>
    );
  }
}

export default RegisterEventStep2;
