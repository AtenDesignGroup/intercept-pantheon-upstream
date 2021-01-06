import React from 'react';
import PropTypes from 'prop-types';
import moment from 'moment';
import { TextField } from '@material-ui/core';

function TimePicker(props) {
  const { value, step, label, onChange } = props;

  const handleChange = (event) => {
    const date = moment(event.target.value, 'HH:mm');
    onChange(date.isValid() ? date.toDate() : null);
  };

  return (
    <TextField
      label={label}
      type="time"
      onChange={handleChange}
      value={moment(value).format('HH:mm')}
      className="time-filter"
      InputLabelProps={{
        shrink: true,
        className: 'time-filter__label',
      }}
      inputProps={{
        step,
      }}
    />
  );
}

TimePicker.propTypes = {
  value: PropTypes.instanceOf(Date),
  label: PropTypes.string,
  step: PropTypes.number,
};

TimePicker.defaultProps = {
  value: new Date(),
  label: 'time',
  step: 900, // 15 min
};

export default TimePicker;
