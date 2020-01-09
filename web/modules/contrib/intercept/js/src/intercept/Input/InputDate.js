import React from 'react';
import PropTypes from 'prop-types';
import { withStyles } from '@material-ui/core/styles';
import moment from 'moment';
import MomentUtils from 'material-ui-pickers/utils/moment-utils';
import DatePicker from 'material-ui-pickers/DatePicker';
import MuiPickersUtilsProvider from 'material-ui-pickers/utils/MuiPickersUtilsProvider';
import { withFormsy, propTypes, defaultProps } from 'formsy-react';
import interceptClient from 'interceptClient';

const { utils } = interceptClient;

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

  componentDidUpdate() {
    // Force this component to be treated like a controlled component
    // by updating formsy with passed prop values.
    if (this.props.value !== this.props.getValue()) {
      this.props.setValue(this.props.value);
    }
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
      required,
      label,
      helperText,
      maxDate,
      maxDateMessage,
      minDate,
      minDateMessage
    } = this.props;


    // const value = this.props.getValue();
    // const inputValue = value === '' ? null : value;
    const inputValue = this.constructor.denormalize(this.props.getValue())


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
          error={!this.props.isValid()}
          helperText={this.props.getErrorMessage() || helperText}
          maxDate={maxDate}
          maxDateMessage={maxDateMessage}
          minDate={minDate}
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
