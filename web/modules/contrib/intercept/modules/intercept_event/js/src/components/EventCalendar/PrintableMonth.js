/* eslint-disable react/no-multi-comp */
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';

/* eslint-disable */
import interceptClient from 'interceptClient';
import drupalSettings from 'drupalSettings';
/* eslint-enable */

import moment from 'moment';

import { filter } from 'lodash';
import get from 'lodash/get';
import memoize from 'lodash/memoize';
import uniq from 'lodash/uniq';

import { v4 as uuidv4 } from 'uuid';

import PrintableClosedSummary from './PrintableClosedSummary';
import PrintableEventSummary from './PrintableEventSummary';
import PrintableLocationLegend from './PrintableLocationLegend';

const { constants, select, utils } = interceptClient;
const c = constants;

const locationClosings = get(drupalSettings, 'intercept.location_closings') || [];

const daysOfTheWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

class PrintableMonth extends React.Component {
  constructor(props) {
    super(props);
    this.datesInRange = memoize(this.constructor.datesInRange.bind(this));
    this.eventList = this.constructor.eventList.bind(this);
    this.eventLocations = memoize(this.constructor.eventLocations.bind(this));
  }

  render() {
    const { date, events, filters } = this.props;
    const dates = this.datesInRange(date);
    const locations = this.eventLocations(events);
    const header = daysOfTheWeek.map(day => (
      <div key={day} className={'print-cal__cell rbc-header'}>
        <span className={'print-cal__cell-day'}>{day}</span>
      </div>
    ));

    const days = dates.map(day => (
      <div key={day.key} className={`print-cal__cell print-cal__cell--${day.inScope ? 'in-scope' : 'not-in-scope'}`}>
        <h3 className={'print-cal__cell-day'}>{day.label}</h3>
        {day.inScope && this.eventList(day.date, events, filters)}
      </div>
    ));

    return (
      <div className={'print-cal'}>
        <div className={'print-cal__header'}>{header}</div>
        <div className={'print-cal__body'}>{days}</div>
        <PrintableLocationLegend locations={locations} />
      </div>
    );
  }
}

PrintableMonth.title = date => moment.tz(date, utils.getUserTimezone()).format('MMMM YYYY');

PrintableMonth.navigate = (date, action) => {
  switch (action) {
    case 'PREV':
      return moment
        .tz(date, utils.getUserTimezone())
        .subtract(1, 'months')
        .toDate();

    case 'NEXT':
      return moment
        .tz(date, utils.getUserTimezone())
        .add(1, 'months')
        .toDate();

    default:
      return date;
  }
};

PrintableMonth.datesInRange = (date) => {
  const dates = [];
  const baseDate = moment.tz(date, utils.getUserTimezone());

  const startOfMonth = baseDate.clone().startOf('month');
  const endOfMonth = baseDate.clone().endOf('month');
  const startDate = startOfMonth.clone().startOf('week');
  const endDate = endOfMonth
    .clone()
    .endOf('week')
    .toDate();
  const currentDate = startDate.clone();

  while (currentDate.toDate() <= endDate) {
    const cd = currentDate.toDate();
    dates.push({
      date: cd,
      key: currentDate.format('MMDD'),
      label: currentDate.format('DD'),
      day: currentDate.format('ddd'),
      inScope: cd >= startOfMonth.toDate() && cd <= endOfMonth.toDate(),
    });
    currentDate.add(1, 'days');
  }
  return dates;
};

const isSameDay = (a, b) => moment(a)
  .tz(utils.getUserTimezone())
  .format('YYMMDD') === moment(b)
  .tz(utils.getUserTimezone())
  .format('YYMMDD');

const isWithinRange = (day, start, end) => moment(day)
  .tz(utils.getUserTimezone())
  .isBetween(
    moment(utils.dateFromDrupal(start)),
    moment(utils.dateFromDrupal(end)),
  );

PrintableMonth.eventList = (day, events, filters) => {
  const locationFilters = filters[c.TYPE_LOCATION] || [];
  const closings = uniq(filter(locationClosings, (item) => {
    if (locationFilters.length === 0) {
      return isSameDay(get(item, 'start'), day) || isWithinRange(day, get(item, 'start'), get(item, 'end'));
    }
    const commonLocations = locationFilters.filter(location => get(item, 'locations').includes(location));
    return (isSameDay(get(item, 'start'), day) || isWithinRange(day, get(item, 'start'), get(item, 'end'))) && commonLocations.length > 0;
  },
  ));
  const items = events.filter(
    item =>
      moment(utils.dateFromDrupal(get(item, 'data.attributes.field_date_time.value')))
        .tz(utils.getUserTimezone())
        .format('MMDD') === moment(day)
        .tz(utils.getUserTimezone())
        .format('MMDD'),
  );
  if (items.length > 0 || closings.length > 0) {
    return (
      <div className={'print-cal__cell-events'}>
        {items.map(item => (
          <PrintableEventSummary id={item.data.id} key={item.data.id} />
        ))}
        {closings.map(item => (
          <PrintableClosedSummary closing={item} key={uuidv4()} />
        ))}
      </div>
    );
  }

  return null;
};

PrintableMonth.eventLocations = (events) => {
  const locationIds = events
    .map(event => get(event, 'data.relationships.field_location.data', [])
      .map(location => location.id)
      .pop(),
    );
  return uniq(locationIds);
};

PrintableMonth.propTypes = {
  date: PropTypes.instanceOf(Date),
  events: PropTypes.array,
};

PrintableMonth.defaultProps = {
  events: [],
  date: new Date(),
};

const mapStateToProps = state => ({
  locations: select.records(c.TYPE_LOCATION)(state),
});

export default connect(mapStateToProps)(PrintableMonth);
