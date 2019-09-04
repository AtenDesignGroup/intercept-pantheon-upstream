// React
import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';

import Formsy from 'formsy-react';

import moment from 'moment';

// Lodash
import map from 'lodash/map';

// Material UI
import Button from '@material-ui/core/Button';

// Intercept
import interceptClient from 'interceptClient';

// Components
import CurrentFilters from 'intercept/CurrentFilters';
import DateFilter from 'intercept/DateFilter';
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

class EventFilters extends PureComponent {
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
    const { showDate, filters } = this.props;
    let currentFilters = currentFiltersConfig(filters);
    if (!showDate) {
      currentFilters = currentFilters.filter(f => f.key !== c.DATE);
    }

    return (
      <div className="">
        <Formsy className="">
          <div className="l--subsection">
            <h4 className="section-title--secondary">Filter Rooms By</h4>
            <KeywordFilter
              handleChange={this.onInputChange(c.KEYWORD)}
              value={filters[c.KEYWORD]}
              name={c.KEYWORD}
              label={labels[c.KEYWORD]}
            />
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
            <SelectResource
              multiple
              type={c.TYPE_ROOM_TYPE}
              name={c.TYPE_ROOM_TYPE}
              handleChange={this.onInputChange(c.TYPE_ROOM_TYPE)}
              value={filters[c.TYPE_ROOM_TYPE]}
              label={labels[c.TYPE_ROOM_TYPE]}
              chips
            />
            <InputNumber
              label={labels[ATTENDEES]}
              value={filters[ATTENDEES]}
              onChange={this.onAttendeesChange}
              name={'attendees'}
              min={0}
              int
            />
          </div>
          <div className="l--subsection">
            <h4 className="section-title--secondary">
              Show Available
              {/* <Button
                variant="raised"
                size="small"
                color="primary"
                type="submit"
                className="button button--primary"
                onClick={this.onFilterNow}
              >
              Now
              </Button> */}
            </h4>
            <InputCheckbox
              label="Available Now"
              checked={filters[NOW]}
              onChange={() => this.onNowChange()}
              value={NOW}
              name={NOW}
            />
            <InputDate
              handleChange={this.onDateChange}
              defaultValue={null}
              value={filters[c.DATE] || null}
              name={c.DATE}
              clearable
              validations="isFutureDate"
              validationError="Date must be in the future"
              disabled={filters[NOW]}
            />
            <SelectSingle
              name={TIME}
              handleChange={this.onInputChange(TIME)}
              value={filters[TIME]}
              label={labels[TIME]}
              disabled={filters[NOW] || !filters[c.DATE]}
              options={timeOptions}
              clearable
            />
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
        </Formsy>
      </div>
    );
  }
}

EventFilters.propTypes = {
  onChange: PropTypes.func.isRequired,
  showDate: PropTypes.bool,
  filters: PropTypes.object,
};

EventFilters.defaultProps = {
  showDate: true,
  filters: {},
};

export default EventFilters;
