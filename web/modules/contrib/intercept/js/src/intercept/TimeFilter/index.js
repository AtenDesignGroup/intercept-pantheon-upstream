import React from 'react';
import PropTypes from 'prop-types';
import { withStyles } from '@material-ui/core/styles';
import moment from 'moment';
import MomentUtils from 'material-ui-pickers/utils/moment-utils';
import TimePicker from 'material-ui-pickers/TimePicker';
import MuiPickersUtilsProvider from 'material-ui-pickers/utils/MuiPickersUtilsProvider';

const styles = theme => ({
  container: {
    display: 'flex',
    flexWrap: 'wrap',
  },
  textField: {
    marginLeft: theme.spacing.unit,
    marginRight: theme.spacing.unit,
    width: 200,
  },
});

const InputLabelProps = value => ({
  shrink: value !== null,
  className: 'time-filter__label',
});

function TimeFilter(props) {
  const { value, label, handleChange } = props;
  const inputValue = value === '' ? null : value;
  const onChange = date => handleChange(date.toDate());
  const onClear = () => handleChange(null);

  return (
    <MuiPickersUtilsProvider utils={MomentUtils} moment={moment}>
      <TimePicker
        onChange={onChange}
        onClear={onClear}
        clearable={props.clearable}
        label={label}
        InputLabelProps={InputLabelProps(inputValue)}
        value={inputValue}
        className="time-filter"
      />
    </MuiPickersUtilsProvider>
  );
}

TimeFilter.propTypes = {
  value: PropTypes.instanceOf(Date),
  label: PropTypes.string,
  handleChange: PropTypes.func.isRequired,
};

TimeFilter.defaultProps = {
  value: null,
  label: 'Time',
};

export default withStyles(styles)(TimeFilter);
