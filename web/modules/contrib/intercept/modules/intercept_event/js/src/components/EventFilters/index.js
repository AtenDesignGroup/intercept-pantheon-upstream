// React
import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';

// Formsy
import Formsy from 'formsy-react';

// Lodash
import get from 'lodash/get';
import map from 'lodash/map';

/* eslint-disable */
// Intercept
import interceptClient from 'interceptClient';

// Components
import CurrentFilters from 'intercept/CurrentFilters';
import InputDate from 'intercept/Input/InputDate';
import KeywordFilter from 'intercept/KeywordFilter';
import SelectResource from 'intercept/SelectResource';
import RadioGroup from 'intercept/RadioGroup/RadioGroup';
/* eslint-enable */

const { constants } = interceptClient;
const c = constants;

const DESIGNATION = 'designation';
const DESIGNATION_OPTIONS = map(get(drupalSettings, 'intercept.events.field_event_designation.options', {}), (value, key) => ({ key, value }));


const labels = {
  [c.TYPE_EVENT_TYPE]: 'Event Type',
  [c.TYPE_LOCATION]: 'Location',
  [c.TYPE_AUDIENCE]: 'Audience',
  [c.DATE_START]: 'After Date',
  [c.DATE_END]: 'Before Date',
  [c.KEYWORD]: 'Keyword',
  [DESIGNATION]: 'Event Designation',
};

const currentFiltersConfig = filters =>
  map(filters, (value, key) => ({
    key,
    value,
    label: labels[key],
    type: key,
  }));

class EventFilters extends PureComponent {
  constructor(props) {
    super(props);

    this.onFilterChange = this.onFilterChange.bind(this);
    this.onDateStartChange = this.onDateStartChange.bind(this);
    this.onDateEndChange = this.onDateEndChange.bind(this);
    this.onInputChange = this.onInputChange.bind(this);
  }

  onFilterChange(key, value) {
    const newFilters = { ...this.props.filters, [key]: value };
    this.props.onChange(newFilters);
  }

  onInputChange(key) {
    return (event) => {
      this.onFilterChange(key, event.target.value);
    };
  }

  onDateStartChange(value) {
    this.onFilterChange(c.DATE_START, value);
  }

  onDateEndChange(value) {
    this.onFilterChange(c.DATE_END, value);
  }

  render() {
    const { showDate, filters, view } = this.props;

    let currentFilters = currentFiltersConfig(filters);

    if (!showDate) {
      currentFilters = currentFilters.filter(f => [c.DATE_START, c.DATE_END].indexOf(f.key) < 0);
    }

    return (
      <div className={`filters filters--${view === 'calendar' ? '4up' : '3up'}`}>
        <h3 className="filters__heading">Filter</h3>
        <Formsy className="filters__inner filters__inputs">
          <div className="filters__inputs-inner">
            <KeywordFilter
              handleChange={this.onInputChange(c.KEYWORD)}
              value={filters[c.KEYWORD]}
              name={c.KEYWORD}
              label={labels[c.KEYWORD]}
            />
            <SelectResource
              multiple
              labels
              type={c.TYPE_LOCATION}
              name={c.TYPE_LOCATION}
              handleChange={this.onInputChange(c.TYPE_LOCATION)}
              value={filters[c.TYPE_LOCATION]}
              label={labels[c.TYPE_LOCATION]}
            />
            <SelectResource
              multiple
              labels
              type={c.TYPE_EVENT_TYPE}
              name={c.TYPE_EVENT_TYPE}
              handleChange={this.onInputChange(c.TYPE_EVENT_TYPE)}
              value={filters[c.TYPE_EVENT_TYPE]}
              label={labels[c.TYPE_EVENT_TYPE]}
            />
            <SelectResource
              multiple
              labels
              type={c.TYPE_AUDIENCE}
              name={c.TYPE_AUDIENCE}
              handleChange={this.onInputChange(c.TYPE_AUDIENCE)}
              value={filters[c.TYPE_AUDIENCE]}
              label={labels[c.TYPE_AUDIENCE]}
            />
            {showDate && (
              <InputDate
                handleChange={this.onDateStartChange}
                defaultValue={null}
                value={filters[c.DATE_START]}
                name={c.DATE_START}
                label={labels[c.DATE_START]}
              />
            )}
            {showDate && (
              <InputDate
                handleChange={this.onDateEndChange}
                defaultValue={null}
                value={filters[c.DATE_END]}
                name={c.DATE_END}
                minDate={filters[c.DATE_START] || undefined}
                label={labels[c.DATE_END]}
              />
            )}
            <RadioGroup
              ariaLabel="Event Designation"
              checked={filters[DESIGNATION]}
              onChange={(value) => {this.onFilterChange(DESIGNATION, value);}}
              value={filters[DESIGNATION]}
              name={DESIGNATION}
              options={DESIGNATION_OPTIONS}
            />
          </div>
        </Formsy>
        <div className="filters__current">
          <CurrentFilters
            filters={currentFilters.filter(f => [DESIGNATION].indexOf(f.key) < 0)}
            onChange={this.onFilterChange}
          />
        </div>
      </div>
    );
  }
}

EventFilters.propTypes = {
  onChange: PropTypes.func.isRequired,
  showDate: PropTypes.bool,
  filters: PropTypes.object,
  view: PropTypes.string,
};

EventFilters.defaultProps = {
  showDate: true,
  filters: {},
  view: null,
};

export default EventFilters;
