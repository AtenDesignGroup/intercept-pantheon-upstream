import React from 'react';
import PropTypes from 'prop-types';
import TextField from '@material-ui/core/TextField';
import { withFormsy, propTypes, defaultProps } from 'formsy-react';

class InputNumber extends React.PureComponent {

  componentDidUpdate() {
    // Force this component to be treated like a controlled component
    // by updating formsy with passed prop values.
    if (this.props.value !== this.props.getValue()) {
      this.props.setValue(this.props.value);
    }
  }

  render() {
    const { step, label, onChange, min, max, int, required } = this.props;

    const handleChange = (event) => {
      const parse = int ? parseInt : parseFloat;
      const v = event.target.value ? parse(event.target.value) : null;
      this.props.setValue(v);
      onChange(v);
    };

    const value = this.props.getValue();

    return (
      <TextField
        label={label}
        required={required}
        type="number"
        onChange={handleChange}
        value={value || ''}
        error={!this.props.isValid()}
        helperText={this.props.getErrorMessage() || this.props.helperText}
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
  ...defaultProps,
  value: null,
  label: 'Number',
  step: 1,
};

export default withFormsy(InputNumber);
