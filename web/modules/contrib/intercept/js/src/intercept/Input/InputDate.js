import React from 'react';
import PropTypes from 'prop-types';
import { withStyles } from '@material-ui/core/styles';
import moment from 'moment';
import MomentUtils from '@date-io/moment';
import { DatePicker, MuiPickersUtilsProvider } from '@material-ui/pickers';
import { withFormsy, propTypes, defaultProps } from 'formsy-react';
import interceptClient from 'interceptClient';

const { utils } = interceptClient;

const styles = theme => ({
  container: {
    display: 'flex',
    flexWrap: 'wrap',
  },
  textField: {
    marginLeft: theme.spacing(1),
    marginRight: theme.spacing(1),
    width: 200,
  },
});

const InputLabelProps = value => ({
  shrink: value !== null,
  className: 'date-filter__label',
});

class InputDate extends React.Component {
  /**
   * Normalizes date to the correct timezone.
   * For some reason, an initial date value is timezone agnostic
   * but subsequent clicks are not. This will ensure the date value
   * is in the desired timezone.
   * @param {Moment|null} date
   *   The incoming date value from the input.
   * @return {Date}
   *    A normalized date set to the start of day in the desired timezone.
   */
  static normalize(date) {
    let d = null;

    if (date !== null) {
      const value = date['_z']
        ? date.clone()
        : moment.tz(date.format('YYYY-MM-DD'), 'YYYY-MM-DD', utils.getUserTimezone());

      d = value
        .startOf('day')
        .toDate();
    }

    return d;
  }

  /**
   * Denormalize a date to the user's timezone.
   * @param {Date} date
   */
  static denormalize(date) {
    if (!date || date === '') {
      return null;
    }

    return moment.tz(date, utils.getUserTimezone());
  }

  onChange = (date) => {
    const d = this.constructor.normalize(date);

    this.props.setValue(d);
    this.props.handleChange(d);
  };

  onClear = () => this.onChange(null);

  render() {
    const {
      clearable,
      disabled,
      errorMessage,
      isValid,
      required,
      label,
      helperText,
      maxDate,
      maxDateMessage,
      minDate,
      minDateMessage,
      value,
    } = this.props;

    const inputValue = this.constructor.denormalize(value);


    return (
      <MuiPickersUtilsProvider utils={MomentUtils} moment={moment}>
        <DatePicker
          onChange={this.onChange}
          onClear={this.onClear}
          clearable={clearable}
          disabled={disabled}
          label={label}
          required={required}
          InputLabelProps={InputLabelProps(inputValue)}
          value={inputValue}
          className="date-filter input input--date"
          error={!isValid}
          helperText={errorMessage || helperText}
          maxDate={maxDate && this.constructor.denormalize(maxDate)}
          maxDateMessage={maxDateMessage}
          minDate={minDate && this.constructor.denormalize(minDate)}
          minDateMessage={minDateMessage}
        />
      </MuiPickersUtilsProvider>
    );
  }
}

InputDate.propTypes = {
  ...propTypes,
  value: PropTypes.instanceOf(Date),
  handleChange: PropTypes.func.isRequired,
  clearable: PropTypes.bool,
  disabled: PropTypes.bool,
  label: PropTypes.string,
};
// Specifies the default values for props:
InputDate.defaultProps = {
  ...defaultProps,
  value: null,
  label: 'Date',
  clearable: true,
  disabled: false,
};

export default withStyles(styles)(withFormsy(InputDate));
