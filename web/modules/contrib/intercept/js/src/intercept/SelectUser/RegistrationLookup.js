import React from 'react';
import PropTypes from 'prop-types';

// Lodash
import get from 'lodash/get';
import debounce from 'lodash/debounce';

import CheckCircle from '@material-ui/icons/CheckCircle';
import Error from '@material-ui/icons/Error';
import Warning from '@material-ui/icons/Warning';

import { v4 as uuidv4 } from 'uuid';

import { InputAdornment, TextField, CircularProgress } from '@material-ui/core';

const EMPTY = 'empty';
const VALID = 'valid';
const INVALID = 'invalid';
const ERROR = 'error';
const LOADING = 'loading';

class RegistrationLookup extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      status: EMPTY,
      value: null,
      currentFetch: null,
    };

    this.handleChange = debounce(this.handleChange, 300);
  }

  /**
   * setStatus()
   * Sets the current status.
   * @param string
   */
  setStatus = (status) => {
    this.setState({ status });
  };

  getAdornment = () => {
    const { status } = this.state;
    let adornment = null;

    switch (status) {
      case ERROR:
        adornment = <Error color="error" />;
        break;
      case INVALID:
        adornment = <Warning color="action" />;
        break;
      case VALID:
        adornment = <CheckCircle color="primary" />;
        break;
      case LOADING:
        adornment = <CircularProgress color="primary" size={20} />;
        break;
      default:
        adornment = null;
    }
    return adornment ? <InputAdornment>{adornment}</InputAdornment> : null;
  };

  getHelperText = () => {
    const { status } = this.state;

    switch (status) {
      case ERROR:
        return 'Error: Try Again';
      case INVALID:
        return 'No account found';
      case VALID:
        return 'Success!';
      case LOADING:
        return 'Loading...';
      case EMPTY:
        return 'Enter username or card number';
      default:
        return null;
    }
  };

  fetchRegistration = (barcode) => {
    const uuid = uuidv4();

    const { setStatus } = this;
    this.setState({
      currentFetch: uuid,
      status: LOADING,
    });

    return fetch('/api/customer/register', {
      credentials: 'same-origin',
      method: 'POST',
      body: JSON.stringify({ barcode }),
    })
      .then(res => res.text())
      .then(this.handleResponse(uuid))
      .catch((e) => {
        setStatus(ERROR);
      });
  };

  handleChange(value) {
    // If the value is empty, cancel current fetch
    // and don't bother with a new one.
    if (!value) {
      this.setState({
        currentFetch: null,
        status: EMPTY,
      });
    }
    else {
      this.fetchRegistration(value);
    }
  }

  handleResponse = fetchUuid => (res) => {
    if (fetchUuid !== this.state.currentFetch) {
      return;
    }

    const values = JSON.parse(res);
    let value = null;
    let status = EMPTY;

    if (!values) {
      status = ERROR;
    }
    else if (values.uuid && values.name) {
      status = VALID;
      value = values;
      // Call the onSuccess handler passed in.
      this.props.onSuccess(values);
    }
    else {
      status = INVALID;
      this.props.onFailure();
    }

    this.setState({
      status,
      value,
      currentFetch: null,
    });
  };

  componentDidUpdate(oldProps) {
    if (oldProps.value !== this.props.value && this.props.value === '') {
      this.setStatus(EMPTY);
    }
  }

  render() {
    const id = `registration-lookup--${this.props.name}`;
    const { disabled, required, value } = this.props;

    return (
      <TextField
        label="Username or Card Number"
        type="text"
        disabled={disabled}
        onChange={(event) => {
          this.props.onChange(event.target.value);
          this.handleChange(event.target.value);
        }}
        className="input input--text input--user"
        InputLabelProps={{
          className: 'input__label',
          htmlFor: id,
        }}
        inputProps={{
          id,
          className: 'input',
        }}
        InputProps={{
          endAdornment: this.getAdornment(),
        }}
        error={status === ERROR}
        helperText={this.getHelperText()}
        required={required}
        fullWidth
        value={value}
      />
    );
  }
}

RegistrationLookup.defaultProps = {
  disabled: false,
  required: false,
};

RegistrationLookup.propTypes = {
  name: PropTypes.string.isRequired,
  onSuccess: PropTypes.func.isRequired,
  onFailure: PropTypes.func.isRequired,
  disabled: PropTypes.bool,
  required: PropTypes.bool,
  value: PropTypes.string,
};

export default RegistrationLookup;
