import React from 'react';
import PropTypes from 'prop-types';
import 'react-app-polyfill/stable';

import isEmpty from 'lodash/isEmpty';
import get from 'lodash/get';

import Formsy from 'formsy-react';
import {
  isIE,
  isEdge,
  isEdgeChromium,
  browserVersion
} from "react-device-detect";

// Intercept Components
/* eslint-disable */
import interceptClient from 'interceptClient';
import DialogConfirm from 'intercept/Dialog/DialogConfirm';

import LoadingIndicator from 'intercept/LoadingIndicator';
import PageSpinner from 'intercept/PageSpinner';
import InputText from 'intercept/Input/InputText';

/* eslint-enable */

import { Button } from '@material-ui/core';

function FormWrapper(props) {
  return (
    <div className="form">
      <h2 className="form__heading">Register as a Guest</h2>
      {props.children}
    </div>
  );
}

class RegisterEventStep1 extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      canSubmit: false,
      emailChecked: false,
      guestRegistrationExists: false,
      registrationExists: false,
      user: null,
      dialogOpen: false,
    };
  }

  isOutdatedBrowser() {
    if (isIE) {
      return true;
    } else if (isEdge && !isEdgeChromium && browserVersion < 79) {
      return true;
    }
    return false;
  }

  onValueChange(key) {
    return (value) => {
      this.updateValue(key, value);
    };
  }

  onEmailChange(email) {
    return (value) => {
      this.setState({ emailChecked: false });
      this.handleEmailChange(value);
      this.updateValue(email, value);
    };
  }

  updateValue(key, value) {
    const newValues = { ...this.props.values, [key]: value };
    this.props.onChange(newValues);
  }

  handleEmailChange = (email) => {
    const validEmail = /^((([!#$%&'*+\-/=?^_`{|}~\w])|([!#$%&'*+\-/=?^_`{|}~\w][!#$%&'*+\-/=?^_`{|}~\.\w]{0,}[!#$%&'*+\-/=?^_`{|}~\w]))[@]\w+([-.]\w+)*\.\w+([-.]\w+)*)$/;
    if (validEmail.test(email)) {
      this.fetchUserIdByEmail(email);
      this.fetchRegistrationsByEmail(email);
    }
  };

  /**
   * Make an API request for a user by email.
   * @param {String} email
   *  A guest email.
   *
   * @return {Promise}
   *  The Promise returned from the fetch.
   */
  fetchUserIdByEmail(email, callback = res => res) {
    fetch('/api/user/email-exists', {
      credentials: 'same-origin',
      method: 'POST',
      body: JSON.stringify({ email }),
    })
      .then(res => res.text())
      .then(res => callback(this.handleEmailResponse(JSON.parse(res))))
      .catch((e) => {
        console.log(e);
      });
  }

  handleEmailResponse = (res) => {
    this.setState({ emailChecked: true });
    if (!isEmpty(res)) {
      this.setState({ user: true });
      Object.entries(res).forEach(id => this.fetchRegistrationsByUserId(Object.values(id).pop()));
      this.openDialog();
    }
  };

  /**
   * Make an API request for event registrations by user.
   *
   * @param {Number} uid
   *  The user ID.
   *
   * @return {Promise}
   *  The Promise returned from the fetch.
   */
  fetchRegistrationsByUserId = (uid) => {
    const { event } = this.props;
    const eventId = get(event, 'data.attributes.drupal_internal__nid');

    fetch('/api/event/user-event-registrations', {
      credentials: 'same-origin',
      method: 'POST',
      body: JSON.stringify({ eventId, uid }),
    })
      .then(res => res.text())
      .then(res => this.handleRegistrationResponse(JSON.parse(res)))
      .catch((e) => {
        console.log(e);
      });
  }

  /**
   * Make an API request for event registrations by email.
   *
   * @param {Number} email
   *  The user ID.
   *
   * @return {Promise}
   *  The Promise returned from the fetch.
   */
  fetchRegistrationsByEmail = (email) => {
    const { event } = this.props;
    const eventId = get(event, 'data.attributes.drupal_internal__nid');

    fetch('/api/event/guest-event-registrations', {
      credentials: 'same-origin',
      method: 'POST',
      body: JSON.stringify({ eventId, email }),
    })
      .then(res => res.text())
      .then(res => this.handleGuestRegistrationResponse(JSON.parse(res)))
      .catch((e) => {
        console.log(e);
      });
  }

  handleRegistrationResponse = (res) => {
    if (!isEmpty(res)) {
      this.setState({ registrationExists: true });
      this.openDialog();
    }
  };

  handleGuestRegistrationResponse = (res) => {
    if (!isEmpty(res)) {
      this.setState({ registrationExists: true });
      this.setState({ guestRegistrationExists: true });
      this.openDialog();
    }
  };

  disableButton = () => {
    this.setState({ canSubmit: false });
  }

  enableButton = () => {
    this.setState({ canSubmit: true });
  }

  closeDialog = () => {
    this.setState({ dialogOpen: false });
  }

  openDialog = () => {
    this.setState({ dialogOpen: true });
  }

  buttonText = () => {
    const {
      emailChecked,
      user,
    } = this.state;
    if (emailChecked && user) {
      return 'Continue as Guest';
    }
    return 'Continue';
  }

  hasRegistration = () => this.state.registrationExists;

  hasUserRegistration = () => this.state.registrationExists && this.state.user;

  hasGuestRegistration = () => this.state.guestRegistrationExists;

  isUser = () => this.state.emailChecked && this.state.user;

  dialogProps = () => {
    if (this.hasUserRegistration()) {
      return {
        heading: 'You are already registered for this event.',
        text: 'Please log in to make changes to your registration.',
        cancelText: 'Close',
        onCancel: () => this.closeDialog(),
        confirmText: 'Log In',
        onConfirm: () => {
          window.location.href = this.loginUrl();
        },
      };
    }
    if (this.hasGuestRegistration()) {
      return {
        heading: 'You are already registered for this event.',
        text: 'Please contact a staff member to make changes to your registration.',
        confirmText: 'Ok',
        onConfirm: () => this.closeDialog(),
      };
    }
    if (this.isUser()) {
      return {
        heading: 'Looks like you have an account already.',
        cancelText: 'Continue as Guest',
        onCancel: () => this.closeDialog(),
        confirmText: 'Log In',
        onConfirm: () => {
          window.location.href = this.loginUrl();
        },
      };
    }
    if (this.isOutdatedBrowser()) {
      return {
        heading: 'Please Upgrade Your Browser',
        text: 'In order to register for your event as a guest, please upgrade your browser. Older or outdated browsers can be slow, put your computer at risk and impact some of the features of our website. For the best experience, we recommend updating your browser or switching to the latest version of a supported browser like Chrome, Safari or Firefox.',
        confirmText: 'Ok',
        onConfirm: () => {
          window.location.href = this.registerUrl();
        },
      };
    }
    return {};
  }

  registerUrl = () => {
    const { event } = this.props;
    const eventNid = get(event, 'data.attributes.drupal_internal__nid');

    return `/event/${eventNid}/register`;
  }

  loginUrl = () => {
    const { event } = this.props;
    const eventNid = get(event, 'data.attributes.drupal_internal__nid');

    return `/user/login?destination=/event/${eventNid}/register`;
  }

  isDisabled = () => !this.state.canSubmit
    || (this.state.emailChecked
    && this.state.registrationExists);

  componentDidMount() {
    if (this.isOutdatedBrowser()) {
      this.openDialog();
    }
  }

  render() {
    const {
      onChangeStep,
    } = this.props;

    if (this.isOutdatedBrowser()) {
      return (
        <DialogConfirm
          {...this.dialogProps()}
          open={this.state.dialogOpen}
          onClose={() => this.closeDialog()}
        />
      )
    }
    return (
      <FormWrapper>
        <Formsy
          className="form__main"
          ref={this.form}
          onChange={this.validateForm}
          onValid={() => this.enableButton()}
          onInvalid={() => this.disableButton()}
          validationErrors={this.state.validationErrors}
        >
          <div className="l--subsection">
            <InputText
              label="Email Address"
              onChange={this.onEmailChange('email')}
              name="email"
              required
              validations="isEmail"
              validationErrors={{
                isEmail: 'Email address is not valid.',
              }}
            />
            <InputText
              label="First Name"
              onChange={this.onValueChange('nameFirst')}
              name="nameFirst"
              required
            />
            <InputText
              label="Last Name"
              onChange={this.onValueChange('nameLast')}
              name="nameLast"
              required
            />
            <InputText
              label="Phone Number"
              onChange={this.onValueChange('phoneNumber')}
              validations={{
                matchRegexp: /^\D?(\d{3})\D?\D?(\d{3})\D?(\d{4})$/,
              }}
              validationErrors={{
                matchRegexp: 'Invalid phone number',
              }}
              name="phoneNumber"
              required
            />
            <InputText
              label="ZIP Code"
              onChange={this.onValueChange('zip')}
              validations="isNumeric,isLength:5"
              validationErrors={{
                isNumeric: 'Invalid ZIP Code.',
                isLength: 'ZIP Code must be 5 digits.',
              }}
              name="zip"
              required
            />
          </div>
          <div className="form__actions">
            {(this.hasUserRegistration() || this.isUser()) &&
              <Button
                variant="contained"
                size="small"
                color="primary"
                type="submit"
                className="button button--primary"
                href={() => this.loginUrl()}
              >
                Log In
              </Button>
            }
            <Button
              variant="contained"
              size="small"
              color="primary"
              type="submit"
              className="button button--primary"
              onClick={() => onChangeStep(1)}
              disabled={this.isDisabled()}
            >
              {this.buttonText()}
            </Button>
            <DialogConfirm
              {...this.dialogProps()}
              open={this.state.dialogOpen}
              onClose={() => this.closeDialog()}
            />
          </div>
        </Formsy>
      </FormWrapper>
    );
  }
}

RegisterEventStep1.propTypes = {
  onChangeStep: PropTypes.func.isRequired,
  event: PropTypes.object.isRequired,
  values: PropTypes.shape({
    nameFirst: PropTypes.string,
    nameLast: PropTypes.string,
    phone: PropTypes.string,
    email: PropTypes.string,
    zip: PropTypes.string,
  }).isRequired,
};

RegisterEventStep1.defaultProps = {
  filters: {},
};

export default RegisterEventStep1;
