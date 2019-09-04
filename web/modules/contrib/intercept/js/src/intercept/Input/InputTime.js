import React from 'react';
import PropTypes from 'prop-types';
import TextField from '@material-ui/core/TextField';
import moment from 'moment';
import { withFormsy } from 'formsy-react';
import { propTypes, defaultProps } from 'formsy-react';

import interceptClient from 'interceptClient';
const { utils } = interceptClient;

class InputTime extends React.Component {
  render() {
    const { step, label, onChange, required } = this.props;
    const value = this.props.getValue();

    const handleChange = (event) => {
      const date = moment(event.target.value, 'HH:mm');
      const d = date.isValid() ? date.toDate() : null;
      this.props.setValue(d);
      onChange(d);
    };

    return (
      <TextField
        label={label}
        type="time"
        onChange={handleChange}
        value={moment(value).tz(utils.getUserTimezone()).format('HH:mm')}
        className="input input--time"
        required={required}
        InputLabelProps={{
          shrink: true,
          className: 'input__label',
        }}
        inputProps={{
          step,
        }}
        error={!this.props.isValid()}
        helperText={this.props.getErrorMessage()}
      />
    );
  }
}

InputTime.propTypes = {
  ...propTypes,
  onChange: PropTypes.func.isRequired,
  value: PropTypes.instanceOf(Date),
  label: PropTypes.string,
  step: PropTypes.number,
};

InputTime.defaultProps = {
  ...defaultProps,
  value: new Date(),
  label: 'time',
  step: 900, // 15 min
};

export default withFormsy(InputTime);
