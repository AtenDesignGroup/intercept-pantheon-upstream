import React from 'react';
import PropTypes from 'prop-types';
import { withStyles } from '@material-ui/core/styles';
import moment from 'moment';
import MomentUtils from 'material-ui-pickers/utils/moment-utils';
import DatePicker from 'material-ui-pickers/DatePicker';
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
  className: 'date-filter__label',
});

function DateFilter(props) {
  const {
    value,
    handleChange,
    label,
    minDate,
    maxDate,
  } = props;
  const onChange = date => handleChange(date.toDate());
  const onClear = () => handleChange(null);
  const inputValue = value === '' ? null : value;

  return (
    <MuiPickersUtilsProvider utils={MomentUtils} moment={moment}>
      <DatePicker
        onChange={onChange}
        onClear={onClear}
        clearable
        label={label}
        InputLabelProps={InputLabelProps(inputValue)}
        value={inputValue}
        className="date-filter"
        minDate={minDate}
      />
    </MuiPickersUtilsProvider>
  );
}

DateFilter.propTypes = {
  value: PropTypes.instanceOf(Date),
  handleChange: PropTypes.func.isRequired,
  label: PropTypes.string,
};

// Specifies the default values for props:
DateFilter.defaultProps = {
  value: null,
  label: 'Date',
};

export default withStyles(styles)(DateFilter);
