import React from 'react';
import PropTypes from 'prop-types';
import { withFormsy, propTypes, defaultProps } from 'formsy-react';

import { Checkbox, FormControlLabel } from '@material-ui/core';

class InputCheckbox extends React.Component {
  render() {
    const { label, isValid, onChange, getErrorMessages, isRequired, checked, value } = this.props;

    const handleChange = (event) => {
      onChange(event.target.value);
      this.props.setValue(event.target.value);
    };

    let helperText = this.props.getErrorMessage() || '';

    if (this.props.helperText) {
      helperText = `${this.props.helperText} ${helperText}`;
    }

    return (
      <FormControlLabel
        className="input input--checkbox"
        checked={checked}
        onChange={handleChange}
        value={value}
        control={
          <Checkbox
            className="input--checkbox__checkbox"
          />
        }
        label={label}
      />
    );
  }
}

InputCheckbox.propTypes = {
  ...propTypes,
  onChange: PropTypes.func.isRequired,
  checked: PropTypes.bool,
  value: PropTypes.string.isRequired,
  label: PropTypes.string,
  validators: PropTypes.arrayOf(String),
};

InputCheckbox.defaultProps = {
  ...defaultProps,
  checked: false,
  label: 'Agree',
  validators: [],
};

export default withFormsy(InputCheckbox);
