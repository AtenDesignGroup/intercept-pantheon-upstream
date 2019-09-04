import React, { Component } from 'react'
import PropTypes from 'prop-types'
import BigCalendar from 'react-big-calendar'
import { accessor } from 'react-big-calendar/lib/utils/accessors'
import moment from 'moment'

// remember the browser's local timezone, as it might by used later on
BigCalendar.tz = moment.tz.guess();
// format all dates in BigCalendar as they would be rendered in browser's local timezone (even if later on they won't)
const m = (...args) => moment.tz(...args, BigCalendar.tz);
m.localeData = moment.localeData;

BigCalendar.setLocalizer(
  BigCalendar.momentLocalizer(m)
);

export const convertDateTimeToDate = (datetime, timeZoneName) => {
  const mom = moment.tz(datetime, timeZoneName);
  return new Date(mom.year(), mom.month(), mom.date(), mom.hour(), mom.minute(), 0);
};

export const convertDateToDateTime = (date, timeZoneName) => {
  const dateM = moment.tz(date, BigCalendar.tz);
  return moment.tz({
    year: dateM.year(),
    month: dateM.month(),
    date: dateM.date(),
    hour: dateM.hour(),
    minute: dateM.minute(),
  }, BigCalendar.tz);
};


class TimeZoneAgnosticBigCalendar extends Component {
  static propTypes = {
    events: PropTypes.array,
    onSelectSlot: PropTypes.func,
    onEventDrop: PropTypes.func,
    timeZoneName: PropTypes.string,
    startAccessor: PropTypes.func,
    endAccessor: PropTypes.func,
  };

  static defaultProps = {
    startAccessor: 'start',
    endAccessor: 'end',
  };

  startAccessor = (event) => {
    const start = accessor(event, this.props.startAccessor);
    return convertDateTimeToDate(start, this.props.timeZoneName);
  };

  endAccessor = (event) => {
    const end = accessor(event, this.props.endAccessor);
    return convertDateTimeToDate(end, this.props.timeZoneName);
  };

  render() {
    const { onSelectSlot, onEventDrop, timeZoneName, ...props } = this.props;
    const bigCalendarProps = {
      ...props,
      startAccessor: this.startAccessor,
      endAccessor: this.endAccessor,
      onSelectSlot: onSelectSlot && (({ start, end, slots }) => {
        onSelectSlot({
          start: convertDateToDateTime(start, timeZoneName),
          end: convertDateToDateTime(end, timeZoneName),
          slots: slots.map(date => convertDateToDateTime(date, timeZoneName)),
        });
      }),
      onEventDrop: onEventDrop && (({ event, start, end }) => {
        onEventDrop({
          event,
          start: convertDateToDateTime(start, timeZoneName),
          end: convertDateToDateTime(end, timeZoneName),
        });
      }),
    };
    return <BigCalendar {...bigCalendarProps} />
  }
}
export default TimeZoneAgnosticBigCalendar;
