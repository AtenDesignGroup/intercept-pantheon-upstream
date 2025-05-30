import React from 'react';
import PropTypes from 'prop-types';
import Formsy, { withFormsy, propTypes } from 'formsy-react';
import { TextField } from '@material-ui/core';

class InputNumber extends React.PureComponent {
  render() {
    const {
      errorMessage,
      isValid,
      step,
      label,
      onChange,
      min,
      max,
      int,
      required,
      value,
      name,
    } = this.props;

    const handleChange = (event) => {
      const parse = int ? parseInt : parseFloat;
      const v = event.target.value ? parse(event.target.value) : null;
      this.props.setValue(v);
      onChange(v);
    };

    return (
      <TextField
        id={name}
        label={label}
        required={required}
        type="number"
        onChange={handleChange}
        value={value || ''}
        error={!isValid}
        helperText={errorMessage || this.props.helperText}
        className="input input--number"
        InputLabelProps={{
          // shrink: value,
          className: 'input__label',
        }}
        inputProps={{
          step,
          min,
          max,
        }}
      />
    );
  }
}

InputNumber.propTypes = {
  ...propTypes,
  onChange: PropTypes.func.isRequired,
  value: PropTypes.number,
  label: PropTypes.string,
  step: PropTypes.number,
  int: PropTypes.bool,
};

InputNumber.defaultProps = {
  ...Formsy.defaultProps,
  value: null,
  label: 'Number',
  step: 1,
};

export default withFormsy(InputNumber);
