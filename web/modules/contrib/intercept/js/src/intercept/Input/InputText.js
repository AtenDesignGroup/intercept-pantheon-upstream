import React from 'react';
import PropTypes from 'prop-types';
import TextField from '@material-ui/core/TextField';
import { withFormsy, propTypes, defaultProps } from 'formsy-react';

class InputText extends React.Component {
  render() {
    const {
      label,
      isValid,
      onChange,
      getErrorMessages,
      isRequired,
      disabled
    } = this.props;

    const handleChange = (event) => {
      onChange(event.target.value);
      this.props.setValue(event.target.value);
    };

    let helperText = this.props.getErrorMessage() || '';

    if (this.props.helperText) {
      helperText = `${this.props.helperText} ${helperText}`;
    }

    return (
      <TextField
        label={label}
        type="text"
        disabled={disabled}
        onChange={handleChange}
        value={this.props.getValue()}
        className="input input--text"
        InputLabelProps={{
          // shrink: true,
          className: 'input__label',
        }}
        inputProps={{}}
        error={!isValid()}
        helperText={helperText}
        required={isRequired()}
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
  validators: PropTypes.arrayOf(String),
  disabled: PropTypes.bool,
};

InputText.defaultProps = {
  ...defaultProps,
  value: '',
  label: 'Text',
  validators: [],
  disabled: false,
};

export default withFormsy(InputText);
