import React from 'react';
import PropTypes from 'prop-types';
import Formsy, { withFormsy, propTypes } from 'formsy-react';
import { TextField } from '@material-ui/core';

class InputText extends React.PureComponent {
  render() {
    const {
      errorMessage,
      label,
      isValid,
      onChange,
      isRequired,
      disabled,
      value,
      name,
    } = this.props;

    const handleChange = (event) => {
      onChange(event.target.value);
      this.props.setValue(event.target.value);
    };

    let helperText = errorMessage || '';

    if (this.props.helperText) {
      helperText = `${this.props.helperText} ${helperText}`;
    }

    return (
      <TextField
        label={label}
        id={name}
        type="text"
        disabled={disabled}
        onChange={handleChange}
        value={value}
        className="input input--text"
        InputLabelProps={{
          // shrink: true,
          className: 'input__label',
        }}
        inputProps={{}}
        error={!isValid}
        helperText={helperText}
        required={isRequired}
        fullWidth
      />
    );
  }
}

InputText.propTypes = {
  ...propTypes,
  onChange: PropTypes.func.isRequired,
  value: PropTypes.string,
  label: PropTypes.string,
  name: PropTypes.string,
  validators: PropTypes.arrayOf(String),
  disabled: PropTypes.bool,
};

InputText.defaultProps = {
  ...Formsy.defaultProps,
  value: '',
  label: 'Text',
  validators: [],
  disabled: false,
};

export default withFormsy(InputText);
