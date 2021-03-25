// React
import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';

// Lodash
import get from 'lodash/get';

// Redux
import { connect } from 'react-redux';

// Material UI
import { Button } from '@material-ui/core';

// Intercept
import interceptClient from 'interceptClient';

// Intercept Components
import InputDate from 'intercept/Input/InputDate';
import SelectTime from 'intercept/Select/SelectTime';
import InputCheckbox from 'intercept/Input/InputCheckbox';
import { isFutureTime, isLessThanMaxTime } from '../ReserveRoom';

// Formsy
import Formsy, { addValidationRule } from 'formsy-react';

// Local Components
import withAvailability from './../withAvailability';

const { constants, utils } = interceptClient;
const c = constants;

const SHOW_CLOSED = 'showClosed';

const matchTime = (original, ref) => {
  if (ref instanceof Date === false || original instanceof Date === false) {
    return ref;
  }
  const output = new Date();
  output.setTime(original.getTime());
  output.setHours(ref.getHours());
  output.setMinutes(ref.getMinutes());
  output.setSeconds(ref.getSeconds());
  output.setMilliseconds(ref.getMilliseconds());
  return output;
};
const matchDate = (original, ref) => matchTime(ref, original);

const purposeRequiresExplanation = meetingPurpose =>
  meetingPurpose && meetingPurpose.data.attributes.field_requires_explanation;

addValidationRule('isRequired', (values, value) => value !== '');
addValidationRule('isPositive', (values, value) => value > 0);
addValidationRule(
  'isRequiredIfServingRefreshments',
  (values, value) => !values.refreshments || value !== '',
);
addValidationRule('isRequiredIfMeeting', (values, value) => !values.meeting || value !== '');
addValidationRule('isFutureTime', (values, value) => {
  if (value === null) {
    return true;
  }
  return isFutureTime(value, values[c.DATE]);
});
addValidationRule('isLessThanMaxDate', (values, value) => {
  if (value === null) {
    return true;
  }
  return isLessThanMaxTime(value, values[c.DATE]);
});
addValidationRule('isAfterStart', (values, value) => value === null || value > values.start);
addValidationRule('isOnOrAfterStart', (values, value) => value === null || value >= values.start);
addValidationRule('isBeforeEnd', (values, value) => value === null || value < values.end);
addValidationRule('isOnOrBeforeEnd', (values, value) => value === null || value <= values.end);

function Transition(props) {
  return <Slide direction="up" {...props} />;
}

class ReserveRoomDateForm extends PureComponent {
  constructor(props) {
    super(props);

    this.state = {
      expand: {
        meeting: false,
      },
      canSubmit: false,
    };

    this.form = React.createRef();

    this.toggleState = this.toggleState.bind(this);
    this.onDateChange = this.onDateChange.bind(this);
    this.onInputChange = this.onInputChange.bind(this);
    this.onValueChange = this.onValueChange.bind(this);

    this.disableButton = this.disableButton.bind(this);
    this.enableButton = this.enableButton.bind(this);
  }

  componentDidMount() {
    const { fetchAvailability, values, room } = this.props;
    const { start, end, date } = values;
    const shouldValidateConflicts = !!(room && start && end && date);
    this.mounted = true;

    if (shouldValidateConflicts) {
      fetchAvailability(this.getRoomAvailabilityQuery());
    }
  }

  componentDidUpdate(prevProps) {
    const { fetchAvailability, values, room } = this.props;
    const { start, end, date } = values;
    const hasValues = !!(room && start && end && date);
    const valuesChanged =
      prevProps.room !== room ||
      prevProps.values.end !== end ||
      prevProps.values.start !== start ||
      prevProps.values.date !== date;
    const shouldValidateConflicts = hasValues && valuesChanged && start < end;

    if (shouldValidateConflicts) {
      fetchAvailability(this.getRoomAvailabilityQuery(), () => this.resetForm());
    }

    if (valuesChanged) {
      this.resetForm();
    }
  }

  componentWillUnmount() {
    this.mounted = false;
  }

  resetForm = () => {
    if (get(this, 'form.current')) {
      this.form.current.reset(this.props.values);
    }
  };

  onInputChange(key) {
    return (event) => {
      this.updateValue(key, event.target.value);
    };
  }

  onValueChange(key) {
    return (value) => {
      this.updateValue(key, value);
    };
  }

  onDateChange(value) {
    const start = matchDate(this.props.values.start, value);
    const end = matchDate(this.props.values.end, value);
    this.updateValues({
      [c.DATE]: value,
      start,
      end,
    });
  }

  onStartChange(value) {
    const start = matchTime(value, this.props.values.start);
    const end = matchTime(value, this.props.values.end);
    this.updateValues({
      [c.DATE]: value,
      start,
      end,
    });
  }

  onSwitchChange(key) {
    return (event) => {
      this.updateValue(key, event.target.checked);
      this.setState({
        expand: {
          ...this.state.expand,
          [key]: event.target.checked,
        },
      });
    };
  }

  disableButton() {
    this.setState({ canSubmit: false });
  }

  enableButton() {
    this.setState({ canSubmit: true });
  }

  toggleState(key) {
    return () => {
      this.setState({
        expand: {
          ...this.state.expand,
          [key]: !this.state.expand[key],
        },
      });
    };
  }

  expand(key) {
    return () => {
      this.setState({
        expand: {
          ...this.state.expand,
          [key]: true,
        },
      });
    };
  }

  collapse(key) {
    return () => {
      this.setState({
        expand: {
          ...this.state.expand,
          [key]: false,
        },
      });
    };
  }

  // Get room availability query params based on current rooms and filters.
  getRoomAvailabilityQuery = () => {
    const { values, room } = this.props;
    const start = utils.getDateFromTime(values.start, values[c.DATE]);
    const end = utils.getDateFromTime(values.end, values[c.DATE]);
    const options = {
      rooms: [room],
    };

    // Compute duration of reservation.
    options.duration = utils.getDurationInMinutes(start, end);
    options.start = start;
    options.end = end;

    return options;
  };

  updateValue = (key, value) => {
    const newValues = { ...this.props.values, [key]: value };
    this.props.onChange(newValues);
  };

  updateValues = (value) => {
    const newValues = { ...this.props.values, ...value };
    this.props.onChange(newValues);
  };

  render() {
    const {
      availability,
      disabledTimespans,
      values,
      min,
      max,
      dateLimits,
      step,
      onSubmit,
      room
    } = this.props;
    const isClosed = (min !== null && min === max) || get(availability, `rooms.${room}.is_closed`);
    const exceedsMaxDuration = get(availability, `rooms.${room}.has_max_duration_conflict`);
    const validationErrors = (!utils.userIsStaff() && isClosed) ? { [c.DATE]: 'Location is closed' } : {};
    const conflictProp = utils.userIsStaff()
      ? 'has_reservation_conflict'
      : 'has_conflict';
    let conflictMessage = 'Room is not available at this time';

    const {
      maxDate,
      minDate,
      maxDateDescription,
    } = dateLimits;

    if (!utils.userIsStaff() && isClosed) {
      conflictMessage = 'Location is closed';
    }
    if (!utils.userIsStaff() && exceedsMaxDuration) {
      conflictMessage = 'Reservation exceeds maximum duration';
    }

    if (get(availability, `rooms.${room}.${conflictProp}`) || (!utils.userIsStaff() && isClosed) || (!utils.userIsStaff() && exceedsMaxDuration)) {
      validationErrors.start = conflictMessage;
      validationErrors.end = conflictMessage;
    }

    return (
      <div className="form">
        <Formsy
          className="form__main"
          ref={this.form}
          onValidSubmit={this.onOpenDialog}
          onValid={this.enableButton}
          onInvalid={this.disableButton}
          validationErrors={validationErrors}
        >
          <div className="l--subsection">
            <h4 className="section-title--secondary">Choose a Time</h4>
            
            <div className="form-item">
              <InputDate
                handleChange={this.onDateChange}
                defaultValue={null}
                value={values[c.DATE]}
                name={c.DATE}
                required
                clearable={false}
                validations="isFutureDate,isLessThanMaxDate"
                validationErrors={{
                  isFutureDate: "Date must be in the future",
                  isLessThanMaxDate: maxDateDescription
                }}
                maxDate={maxDate}
                minDate={minDate}
                helperText={maxDateDescription}
              />
              {utils.userIsStaff() && (<InputCheckbox
                label="Show Closed Hours"
                checked={values[SHOW_CLOSED]}
                onChange={() => this.updateValue(SHOW_CLOSED, !values[SHOW_CLOSED])}
                value={SHOW_CLOSED}
                name={SHOW_CLOSED}
              />)}
            </div>
            <div className="form-item">
              <SelectTime
                clearable
                label="Start Time"
                value={values.start}
                onChange={this.onValueChange('start')}
                name="start"
                required
                validations="isFutureTime"
                validationError="Must be in the future"
                min={min}
                max={max}
                step={step}
                disabled={utils.userIsStaff() ? false : isClosed}
                disabledSpans={disabledTimespans}
                disabledExclude={'trailing'}
              />
            </div>
            <div className="form-item">
              <SelectTime
                clearable
                label="End Time"
                value={values.end}
                onChange={this.onValueChange('end')}
                name="end"
                required
                validations={{
                  isFutureTime: true,
                  isAfterStart: true,
                }}
                validationErrors={{
                  isFutureTime: 'Must be in the future',
                  isAfterStart: 'Must be after start time',
                }}
                min={min}
                max={max}
                step={step}
                disabled={utils.userIsStaff() ? false : isClosed}
                disabledSpans={disabledTimespans}
                disabledExclude={'leading'}
              />
            </div>
          </div>

          <div className="form__actions">
            <Button
              variant="contained"
              size="small"
              color="primary"
              type="submit"
              className="button button--primary"
              disabled={!this.state.canSubmit}
              onClick={(e) => {
                e.preventDefault();
                onSubmit();
              }}
            >
              Next
            </Button>
          </div>
        </Formsy>
      </div>
    );
  }
}

ReserveRoomDateForm.propTypes = {
  values: PropTypes.shape({
    date: PropTypes.instanceOf(Date),
    start: PropTypes.string,
    end: PropTypes.string,
  }),
  onChange: PropTypes.func.isRequired,
};

ReserveRoomDateForm.defaultProps = {
  values: {
    date: utils.roundTo(new Date()).toDate(),
    start: null,
    end: null,
  },
  step: 15,
  min: null,
  max: null,
};

const mapStateToProps = (state, ownProps) => ({});

export default connect(mapStateToProps)(withAvailability(ReserveRoomDateForm));
