import React from 'react';
import PropTypes from 'prop-types';
import moment from 'moment';
import { withFormsy, propTypes, defaultProps } from 'formsy-react';
import interceptClient from 'interceptClient';

import { TextField } from '@material-ui/core';

const { utils } = interceptClient;

class InputTime extends React.PureComponent {
  render() {
    const { errorMessage, isValid, step, label, onChange, required, value } = this.props;

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
        error={!isValid}
        helperText={errorMessage}
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
