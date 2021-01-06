/* eslint-disable react/prefer-stateless-function */
import React from 'react';
import PropTypes from 'prop-types';
import { withStyles } from '@material-ui/core/styles';
import { withFormsy, propTypes, defaultProps } from 'formsy-react';

import {
  Radio,
  RadioGroup,
  FormControlLabel,
  FormControl,
  FormLabel,
  FormHelperText,
} from '@material-ui/core';

class Radios extends React.Component {
  render() {
    const {
      label,
      ariaLabel,
      isValid,
      onChange,
      errorMessage,
      required,
      disabled,
      options,
      name,
      value,
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
      <FormControl
        component="fieldset"
        className={'radios'}
        required={required}
        error={!isValid} >
        {label && (<FormLabel component="legend">{label}</FormLabel>)}
        <RadioGroup
          aria-label={ariaLabel}
          name={name}
          value={value}
          className={'radios__group'}
          onChange={handleChange}
        >
          {options.map(option => (
            <FormControlLabel key={option.key} value={option.key} control={<Radio />} label={option.value} className={'radios__radio-label'} />
          ))}
        </RadioGroup>
        {helperText && (<FormHelperText error={!isValid}>{helperText}</FormHelperText>)}
      </FormControl>
    );
  }
}

Radios.propTypes = {
  ...propTypes,
  onChange: PropTypes.func.isRequired,
  options: PropTypes.arrayOf(Object).isRequired,
  value: PropTypes.string,
  label: PropTypes.string,
  ariaLabel: PropTypes.string,
  validators: PropTypes.arrayOf(String),
  disabled: PropTypes.bool,
};

Radios.defaultProps = {
  ...defaultProps,
  value: '',
  label: '',
  ariaLabel: '',
  validators: [],
  disabled: false,
};

export default withFormsy(Radios);
