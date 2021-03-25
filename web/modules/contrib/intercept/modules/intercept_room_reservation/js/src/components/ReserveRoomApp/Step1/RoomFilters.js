// React
import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';

import Formsy from 'formsy-react';

import moment from 'moment';

// Lodash
import map from 'lodash/map';
import get from 'lodash/get';

// Intercept
import interceptClient from 'interceptClient';
import drupalSettings from 'drupalSettings';

// Components
import KeywordFilter from 'intercept/KeywordFilter';
import SelectResource from 'intercept/SelectResource';
import SelectSingle from 'intercept/Select/SelectSingle';
import InputDate from 'intercept/Input/InputDate';
import InputNumber from 'intercept/Input/InputNumber';
import InputCheckbox from 'intercept/Input/InputCheckbox';

const { constants, utils } = interceptClient;
const c = constants;
const ATTENDEES = 'attendees';
const TIME = 'time';
const DURATION = 'duration';
const NOW = 'now';

const labels = {
  [c.TYPE_LOCATION]: 'Location',
  [c.TYPE_ROOM_TYPE]: 'Room Type',
  [ATTENDEES]: 'Number of Attendees',
  [DURATION]: 'Duration',
  [TIME]: 'Time of Day',
  [c.KEYWORD]: 'Keyword',
};

const durationOptions = [
  { key: 'null', value: 'Any' },
  { key: '15', value: '15 min.' },
  { key: '30', value: '30 min.' },
  { key: '60', value: '1 hr.' },
  { key: '120', value: '2 hrs.' },
  { key: '240', value: '4 hrs.' },
];

const timeOptions = [
  { key: null, value: 'Any' },
  { key: 'morning', value: 'Morning' },
  { key: 'afternoon', value: 'Afternoon' },
  { key: 'evening', value: 'Evening' },
];

const currentFiltersConfig = filters =>
  map(filters, (value, key) => ({
    key,
    value,
    label: labels[key],
    type: key,
  }));

class RoomFilters extends PureComponent {
  constructor(props) {
    super(props);
    this.state = {
      default_applied: false,
    };
  }

  componentDidUpdate() {
    if (!this.state.default_applied && !this.props.loading) {
      const { filters } = this.props;
      const defaultLocations = get(drupalSettings, 'intercept.room_reservations.default_locations', []);
      if (defaultLocations.length > 0 && filters[c.TYPE_LOCATION].length === 0) {
        this.onFilterChange(c.TYPE_LOCATION, defaultLocations);
      }
      this.setState({ default_applied: true });
    }
  }

  onFilterChange = (key, value) => {
    const newFilters = { ...this.props.filters, [key]: value };
    this.props.onChange(newFilters);
  };

  onFilterNow = (value) => {
    const { filters } = this.props;
    const now = value;
    const duration = filters.duration || 30;
    const date = utils.getUserStartOfDay();
    const current = parseInt(
      moment()
        .tz(utils.getUserTimezone())
        .format('HHmm'),
      10,
    );
    let time = 'afternoon';
    if (current < 1200) {
      time = 'morning';
    }
    else if (current > 1700) {
      time = 'evening';
    }

    const newFilters = {
      ...filters,
      duration,
      date,
      time,
      now,
    };
    this.props.onChange(newFilters);
  };

  onInputChange = key => (event) => {
    this.onFilterChange(key, event.target.value);
  };

  onDateChange = (value) => {
    this.onFilterChange(c.DATE, value);
  };

  onAttendeesChange = (value) => {
    this.onFilterChange(ATTENDEES, value);
  };

  onNowChange = () => {
    this.onFilterNow(!this.props.filters[NOW]);
  };

  render() {
    const {
      dateLimits,
      showDate,
      filters,
    } = this.props;

    let currentFilters = currentFiltersConfig(filters);

    if (!showDate) {
      currentFilters = currentFilters.filter(f => f.key !== c.DATE);
    }

    const {
      maxDate,
      minDate,
      maxDateDescription,
    } = dateLimits;

    return (
      <div className="">
        <Formsy className="">
          <div className="l--subsection">
            <h4 className="section-title--secondary">Filter Rooms By</h4>
            <div className="form-item">
              <KeywordFilter
                handleChange={this.onInputChange(c.KEYWORD)}
                value={filters[c.KEYWORD]}
                name={c.KEYWORD}
                label={labels[c.KEYWORD]}
              />
            </div>
            <div className="form-item">
              <InputDate
                handleChange={this.onDateChange}
                defaultValue={null}
                value={filters[c.DATE] || null}
                maxDate={maxDate}
                minDate={minDate}
                name={c.DATE}
                clearable
                validations="isFutureDate"
                validationError="Date must be in the future"
                disabled={filters[NOW]}
              />
            </div>
            <div className="form-item">
              <SelectResource
                multiple
                type={c.TYPE_LOCATION}
                name={c.TYPE_LOCATION}
                handleChange={this.onInputChange(c.TYPE_LOCATION)}
                value={filters[c.TYPE_LOCATION]}
                label={labels[c.TYPE_LOCATION]}
                chips
                shouldFetch={false}
              />
            </div>
            <div className="form-item">
              <SelectSingle
                name={TIME}
                handleChange={this.onInputChange(TIME)}
                value={filters[TIME]}
                label={labels[TIME]}
                disabled={filters[NOW] || !filters[c.DATE]}
                options={timeOptions}
                clearable
              />
            </div>
            <div className="form-item">
              <SelectSingle
                name={DURATION}
                handleChange={this.onInputChange(DURATION)}
                value={filters[DURATION]}
                label={labels[DURATION]}
                options={durationOptions}
                disabled={!filters[NOW] && !filters[c.DATE]}
                clearable
              />
            </div>
            <div className="form-item">
              <SelectResource
                multiple
                type={c.TYPE_ROOM_TYPE}
                name={c.TYPE_ROOM_TYPE}
                handleChange={this.onInputChange(c.TYPE_ROOM_TYPE)}
                value={filters[c.TYPE_ROOM_TYPE]}
                label={labels[c.TYPE_ROOM_TYPE]}
                chips
              />
            </div>
            <div className="form-item">
              <InputNumber
                label={labels[ATTENDEES]}
                value={filters[ATTENDEES]}
                onChange={this.onAttendeesChange}
                name={'attendees'}
                min={0}
                int
              />
            </div>
          </div>
          <div className="l--subsection">
            <h4 className="section-title--secondary">
              Show Available
            </h4>
            <InputCheckbox
              label="Available Now"
              checked={filters[NOW]}
              onChange={() => this.onNowChange()}
              value={NOW}
              name={NOW}
            />
          </div>
        </Formsy>
      </div>
    );
  }
}

RoomFilters.propTypes = {
  loading: PropTypes.bool,
  onChange: PropTypes.func.isRequired,
  showDate: PropTypes.bool,
  filters: PropTypes.object,
};

RoomFilters.defaultProps = {
  loading: false,
  showDate: true,
  filters: {},
};

export default RoomFilters;
