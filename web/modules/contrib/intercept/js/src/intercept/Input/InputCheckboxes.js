import React from 'react';
import PropTypes from 'prop-types';
import InputCheckbox from './InputCheckbox';

import union from 'lodash/union';
import without from 'lodash/without';

import {
  FormLabel,
  FormControl,
  FormGroup,
  FormControlLabel,
  FormHelperText,
  Checkbox,
} from '@material-ui/core';

class InputCheckboxes extends React.Component {
  state = {
    gilad: true,
    jason: false,
    antoine: false,
  };

  handleChange = key => event => {
    const {
      onChange,
      value,
    } = this.props;

    onChange(event.target.checked
       ? union(value, [key])
       : without(value, key)
    );
  };

  render() {
    const {
      className,
      label,
      options,
      helperText,
      labelProps,
      value,
    } = this.props;

    const checkboxes = options.map(o => (<FormControlLabel
      key={o.key}
      control={
        <Checkbox
          checked={value.indexOf(o.key) >= 0}
          onChange={this.handleChange(o.key)}
          value={o.key}
          classes={{
            root: 'input-checkboxes__checkbox-input',
            disabled: 'input-checkboxes__checkbox-input--disabled',
            checked: 'input-checkboxes__checkbox-input--checked',
          }}
        />
      }
      label={o.value}
      classes={{
        root: 'input-checkboxes__checkbox',
        label: 'input-checkboxes__checkbox-text',
      }}
    />));


    return (
      <FormControl component="fieldset" className={className} name={name}>
        {label && (<FormLabel
          component="legend"
          classes={{
            root: 'input-checkboxes__label',
            disabled: 'input-checkboxes__label--disabled',
          }}
          {...labelProps} >{label}</FormLabel>)}
        <FormGroup classes={{root: 'input-checkboxes__group'}}>
          {checkboxes}
        </FormGroup>
        {helperText && (<FormHelperText>{helperText}</FormHelperText>)}
      </FormControl>
    );
  }
}

InputCheckboxes.propTypes = {
  onChange: PropTypes.func.isRequired,
  options: PropTypes.arrayOf(
    PropTypes.shape({
      key: PropTypes.string,
      value: PropTypes.string,
    }),
  ),
  value: PropTypes.arrayOf(PropTypes.string),
  label: PropTypes.string,
  name: PropTypes.string.isRequired,
};

InputCheckboxes.defaultProps = {
  checked: false,
  label: 'Agree',
  options: [],
  value: [],
};

export default InputCheckboxes;
