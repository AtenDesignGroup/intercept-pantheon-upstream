import React from 'react';
import PropTypes from 'prop-types';
import { withFormsy, propTypes, defaultProps } from 'formsy-react';

import { Checkbox, FormControlLabel } from '@material-ui/core';

class InputCheckbox extends React.PureComponent {
  render() {
    const { label, isValid, onChange, errorMessage, isRequired, checked, value } = this.props;

    const handleChange = (event) => {
      onChange(event.target.value);
      this.props.setValue(event.target.value);
    };

    let helperText = errorMessage || '';

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
  value: PropTypes.oneOfType([PropTypes.string, PropTypes.bool]).isRequired,
  label: PropTypes.oneOfType([PropTypes.string, PropTypes.object]),
  validators: PropTypes.arrayOf(String),
};

InputCheckbox.defaultProps = {
  ...defaultProps,
  checked: false,
  label: 'Agree',
  validators: [],
};

export default withFormsy(InputCheckbox);
