import React from 'react';
import PropTypes from 'prop-types';
import memoize from 'lodash/memoize';
import { withStyles } from '@material-ui/core/styles';
import { Autocomplete } from '@material-ui/lab';
import { withFormsy, propTypes, defaultProps } from 'formsy-react';

import interceptClient from 'interceptClient';

import {
  InputLabel,
  MenuItem,
  FormControl,
  ListItemText,
  FormHelperText,
  Select,
  TextField,
} from '@material-ui/core';

const { utils } = interceptClient;

const styles = theme => ({
  root: {
    display: 'flex',
    flexWrap: 'wrap',
  },
  formControl: {
    margin: theme.spacing(1),
    minWidth: 120,
    maxWidth: 300,
    width: '100%',
  },
  inputLabel: {
    margin: 0,
  },
});

const MenuListProps = {
  className: 'select-filter__menu-list',
};

const MenuProps = {
  MenuListProps,
  PaperProps: {
    style: {
      maxHeight: 200,
      width: 250,
    },
  },
  getContentAnchorEl: null,
  anchorOrigin: {
    vertical: 'bottom',
    horizontal: 'left',
  },
  className: 'select-filter__menu',
};

class SelectTime extends React.Component {
  /**
   * Creates an array of time options.
   * @param min {Date}
   *  Earliest possible time option.
   * @param max {Date}
   *  Latest possible time option.
   * @param step {Number}
   *  Interval in which options are created in minutes.
   */
  static getOptions(min, max, step, disabledSpans, disabledExclude) {
    const options = [];
    const disabledOptions = this.constructor.getDisabledOptions(
      disabledSpans,
      step,
      disabledExclude,
    );

    if (!min || !max) {
      return options;
    }
    const minDate = utils.getDateFromTime(min);
    const maxDate = utils.getDateFromTime(max);
    const i = utils.roundTo(minDate, step).clone();

    // Abort if the min time is after the max time to avoid an infinite loop.
    if (min >= max) {
      return options;
    }
    do {
      const key = i.tz(utils.getUserTimezone()).format('HHmm');
      const value = utils.getTimeDisplay(i);

      if (key === '0000' && i > minDate) {
        options.push({
          key: '2400',
          value: 'Midnight',
          disabled: disabledOptions.indexOf('2400') >= 0,
        });
      }
      else {
        options.push({
          key,
          value,
          disabled: disabledOptions.indexOf(key) >= 0,
        });
      }

      i.add(step, 'minutes');
    } while (i.toDate() <= maxDate);

    // Remove the first or last option based on disabledExcluded.
    // This is useful to remove the latest time from a list of start times
    // and the earliest time from a list of end times.
    switch (disabledExclude) {
      case 'trailing':
        options.pop();
        break;
      case 'leading':
        options.shift();
        break;
      default:
        break;
    }

    return options;
  }

  /**
   * Creates an array of time options.
   * @param min {Date}
   *  Earliest possible time option.
   * @param max {Date}
   *  Latest possible time option.
   * @param step {Number}
   *  Interval in which options are created in minutes.
   */
  static getDisabledOptions(disabledSpans, step, disabledExclude) {
    // there is an array of start end objects
    // for each object create a array of options that should be disabled
    // concat all the disabled options.

    return disabledSpans.reduce((spans, span) => {
      const min = span.start;
      const max = span.end;

      if (!min || !max) {
        return spans;
      }

      const minDate = utils.getDateFromTime(min);
      const maxDate = utils.getDateFromTime(max);
      const i = utils.roundTo(minDate, step).clone();

      // Abort if the min time is after the max time to avoid an infinite loop.
      if (min >= max) {
        return spans;
      }

      if (disabledExclude === 'leading') {
        i.add(step, 'minutes');
      }

      do {
        const key = i.tz(utils.getUserTimezone()).format('HHmm');

        if (key === '0000' && i > minDate) {
          spans.push('2400');
        }
        else {
          spans.push(key);
        }

        i.add(step, 'minutes');
      } while (disabledExclude === 'trailing' ? i.toDate() < maxDate : i.toDate() <= maxDate);
      return spans;
    }, []);
  }

  constructor(props) {
    super(props);
    // Memoize getOptions() to avoid unneeded date calculations.
    this.options = memoize(this.constructor.getOptions, (...args) => JSON.stringify(args));

    this.state = {
      autocompleteIsOpen: false,
    };
  }

  handleChange = (event, value) => {
    // When clearing an input, the value is null, otherwise it is an object.
    const normalizedValue = value === null
      ? value
      : value.key;
    this.props.setValue(normalizedValue);
    this.props.onChange(normalizedValue);
  };

  render() {
    const {
      errorMessage,
      isValid,
      min,
      max,
      step,
      label,
      disabled,
      disabledSpans,
      disabledExclude,
      value,
    } = this.props;
    const { autocompleteIsOpen } = this.state;
    const options = this.options(min, max, step, disabledSpans, disabledExclude);
    const checkboxId = id => `select-filter--${id}`;
    const checkboxLabel = (text, id) => (
      <label className="select-filter__checkbox-label" htmlFor={id}>
        {text}
      </label>
    );

    return (
      <div className="select-filter input input--select">
        <FormControl className="select-filter__control">
          <InputLabel
            className="select-filter__label"
            htmlFor="select-filter__menu"
            required={this.props.required}
            shrink={autocompleteIsOpen || !!value}
          >
            {label}
          </InputLabel>

          <Autocomplete
            className="select-filter__menu"
            defaultValue={value === null || !value ? '' : options.filter(option => option.key === value).shift()}
            getOptionLabel={option => option.value || ''}
            options={options}
            onChange={this.handleChange}
            onClose={() => this.setState({ autocompleteIsOpen: false })}
            onOpen={() => this.setState({ autocompleteIsOpen: true })}
            getOptionSelected={(option, val) => option.key === val}
            renderOption={option => (
              <MenuItem
                component="div"
                key={option.key}
                disabled={option.disabled}
                value={option.key}
                className="select-filter__menu-item"
              >
                <ListItemText
                  disableTypography
                  primary={checkboxLabel(option.value, checkboxId(option.key))}
                />
              </MenuItem>
            )}
            renderInput={params => (
              <TextField
                {...params}
                aria-describedby="select-time-helper-text"
                disabled={disabled}
                error={!isValid}
                fullWidth
                required={this.props.required}
              />
            )}
          />

          <FormHelperText id="select-time-helper-text" error={!isValid}>
            {errorMessage}
          </FormHelperText>
        </FormControl>
      </div>
    );
  }
}

SelectTime.propTypes = {
  ...propTypes,
  label: PropTypes.string.isRequired,
  value: PropTypes.string,
  min: PropTypes.string,
  max: PropTypes.string,
  step: PropTypes.number,
  onChange: PropTypes.func.isRequired,
  disabledSpans: PropTypes.array,
  disabled: PropTypes.bool,
  disabledExclude: PropTypes.string,
};

SelectTime.defaultProps = {
  ...defaultProps,
  value: null,
  multiple: false,
  min: '0000',
  max: '1159',
  step: 15,
  disabled: false,
  disabledSpans: [],
  disabledExclude: null,
};

export default withStyles(styles, { withTheme: true })(withFormsy(SelectTime));
