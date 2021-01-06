import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { Calendar, momentLocalizer } from 'react-big-calendar';
import { accessor } from 'react-big-calendar/lib/utils/accessors';
import interceptClient from 'interceptClient';
import moment from 'moment';
import withDragAndDrop from 'react-big-calendar/lib/addons/dragAndDrop';

const { utils } = interceptClient;

const DragAndDropCalendar = withDragAndDrop(Calendar);

/**
 *
 * Local TZ = The timezone of the user's browser.
 * Display TZ = The
 *
 * The external state stores the date in its realtime, meaning the date is not modified
 * to account for any timezone.
 *
 * Local time (Stored externally)
 *   Fri Jan 10 2020 07:34:29 GMT+0900 (Korean Standard Time)
 *   Thu Jan 09 2020 17:34:00 GMT-0500 (America/New York)
 *
 * The calendar takes that time, maps the day, hours, min etc. to
 * the Display timezone. This results in a timestamp that is offset
 * to make the Local time match the Display time.
 *
 * Display (America/New York)
 *   Thu Jan 09 2020 17:34:00 GMT+0900 (Korean Standard Time)
 *   Thu Jan 09 2020 03:34:00 GMT-0500 (America/New York)
 *
 * Any dates "exported" out of the calendar need to be converted back into real-time.
 * The is done by overriding the
 */

// Remember the browser's local timezone, as it might by used later on.
Calendar.tz = moment.tz.guess();
// Format all dates in BigCalendar as they would be rendered in browser's local timezone (even if later on they won't)
const m = (...args) => moment.tz(...args, Calendar.tz);
m.localeData = moment.localeData;
const localizer = momentLocalizer(m);

/**
 * Converts a date from the specified timezone, into
 * that same date in the local timezone. This is useful
 * for displaying different timezones in local time.
 * For example:
 *  Converting 12:30pm New York time to 12:30 Los Angeles time.
 * @param {Date} dateTime
 *  Source Date object.
 * @param {String} timeZoneName
 *  Source timezone.
 * @returns {Date}
 *  A new Date where the hours.
 */
export const convertDateTimeToDate = (datetime, timeZoneName) => {
  const mom = moment.tz(datetime, timeZoneName);
  return new Date(mom.year(), mom.month(), mom.date(), mom.hour(), mom.minute(), 0);
};

/**
 * Convert a local Date into the date specified in the BigCalendar timezone
 * @param {Date} date
 *  Internal BigCalendar date
 * @param {String} destinationTimeZone
 *  Named timezone, ex. "America/New York"
 */
export const convertDateToDateTime = (date, destinationTimeZone) => {
  const dateM = moment.tz(date, Calendar.tz);
  return moment.tz({
    year: dateM.year(),
    month: dateM.month(),
    date: dateM.date(),
    hour: dateM.hour(),
    minute: dateM.minute(),
  }, destinationTimeZone).toDate();
};

class TimeZoneAgnosticBigCalendar extends Component {
  static propTypes = {
    date: PropTypes.instanceOf(Date),
    events: PropTypes.array,
    onSelectSlot: PropTypes.func,
    onEventDrop: PropTypes.func,
    timeZoneName: PropTypes.string,
    startAccessor: PropTypes.func,
    endAccessor: PropTypes.func,
  };

  static defaultProps = {
    startAccessor: event => event.start,
    endAccessor: event => event.end,
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
    const {
      date,
      defaultDate,
      onEventDrop,
      onEventResize,
      onNavigate,
      onSelectSlot,
      slotPropGetter,
      timeZoneName,
      ...props } = this.props;
    const bigCalendarProps = {
      ...props,
      startAccessor: this.startAccessor,
      endAccessor: this.endAccessor,
      date: date && convertDateTimeToDate(date, timeZoneName),
      defaultDate: defaultDate && convertDateTimeToDate(defaultDate, timeZoneName),
      localizer,
      getNow: () => convertDateTimeToDate(utils.getUserTimeNow(), timeZoneName),
      onNavigate:
        onNavigate &&
        ((dateTime) => {
          onNavigate(convertDateToDateTime(dateTime, timeZoneName));
        }),
      onSelectSlot:
        onSelectSlot &&
        (({ start, end, slots, ...args }) => {
          onSelectSlot({
            ...args,
            start: convertDateToDateTime(start, timeZoneName),
            end: convertDateToDateTime(end, timeZoneName),
            slots: slots.map(dateTime => convertDateToDateTime(dateTime, timeZoneName)),
          });
        }),
      onEventDrop:
        onEventDrop &&
        (({ start, end, ...args }) => {
          onEventDrop({
            ...args,
            start: convertDateToDateTime(start, timeZoneName),
            end: convertDateToDateTime(end, timeZoneName),
          });
        }),
      onEventResize:
        onEventResize &&
        (({ start, end, ...args }) => {
          onEventResize({
            ...args,
            start: convertDateToDateTime(start, timeZoneName),
            end: convertDateToDateTime(end, timeZoneName),
          });
        }),
      slotPropGetter:
        slotPropGetter &&
        (dateTime => slotPropGetter(convertDateToDateTime(dateTime, timeZoneName))),
    };

    const CalendarComponent = this.props.dnd ? DragAndDropCalendar : Calendar;
    return (<CalendarComponent {...bigCalendarProps} />);
  }
}
export default TimeZoneAgnosticBigCalendar;
