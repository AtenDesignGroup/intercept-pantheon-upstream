import React from 'react';
import PropTypes from 'prop-types';

// Lodash
import get from 'lodash/get';
import map from 'lodash/map';

import BigCalendar from 'intercept/BigCalendar';

import Toolbar from 'react-big-calendar/lib/Toolbar';
import ArrowBack from '@material-ui/icons/ArrowBack';
import ArrowForward from '@material-ui/icons/ArrowForward';
import Button from '@material-ui/core/Button';
import IconButton from '@material-ui/core/IconButton';
import interceptClient from 'interceptClient';
import moment from 'moment';

const { utils } = interceptClient;

const CalendarEvent = (props) => {
  const { event } = props;
  const { data } = event;

  return (
    <div className="calendar-event calendar-event--disabled">
      <p className="calendar-event__title">{event.title}</p>
    </div>
  );
};

class CustomToolbar extends Toolbar {
  render() {
    const { messages, label, maxDate, minDate, date } = this.props;

    return (
      <div className="rbc-toolbar">
        <span className="rbc-btn-group">
          <Button
            variant="raised"
            color="primary"
            size="small"
            className="rbc-btn rbc-btn--today"
            onClick={() => this.navigate('TODAY')}
          >
            {messages.today}
          </Button>
        </span>
        <div className="rbc-toolbar__heading">
          <IconButton
            className={'rbc-toolbar__pager-button rbc-toolbar__pager-button--prev'}
            onClick={() => this.navigate('PREV')}
            color="primary"
            aria-label="Previous"
            variant="flat"
            disabled={minDate && utils.getDateFromDayTimeStamp(minDate) >= date}
          >
            <ArrowBack />
          </IconButton>
          <h2 className="rbc-toolbar__label">{label}</h2>
          <IconButton
            className={'rbc-toolbar__pager-button rbc-toolbar__pager-button--next'}
            onClick={() => this.navigate('NEXT')}
            color="primary"
            aria-label="Next"
            disabled={maxDate && utils.getDateFromDayTimeStamp(maxDate) <= date}
          >
            <ArrowForward />
          </IconButton>
        </div>

        <span className="rbc-btn-group rbc-btn-group--views">{this.viewNamesGroup(messages)}</span>
      </div>
    );
  }
}

const components = parentProps => ({
  event: CalendarEvent,
  toolbar: props => (<CustomToolbar {...parentProps} {...props} />),
});

class RoomAvailabilityCalendar extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      showEvent: false,
      selectedEvent: null,
    };

    this.onSelectEvent = this.onSelectEvent.bind(this);
    this.onHideEvent = this.onHideEvent.bind(this);
  }

  onSelectEvent(event) {
    this.setState({
      showEvent: true,
      selectedEvent: event.data.id,
    });
  }

  onHideEvent() {
    this.setState({
      showEvent: false,
    });
  }

  getDateFromTime = (time) => {
    const hours = parseInt(time.slice(0, 2), 10);
    const minutes = parseInt(time.slice(2), 10);
    const date = new Date('Jan 1, 2000 00:00:00');
    date.setHours(hours);
    date.setMinutes(minutes);

    return date;
  };

  startDateAccessor = prop => (item) => {
    const dateFromDrupal = moment(utils.dateFromDrupal(item[prop]));
    const midnight = moment(this.props.date)
      .tz(utils.getUserTimezone())
      .startOf('day');
    const startDate = moment.max(dateFromDrupal, midnight);
    return startDate.toISOString();
  };

  endDateAccessor = prop => (item) => {
    const dateFromDrupal = moment(utils.dateFromDrupal(item[prop]));
    const midnight = moment(this.props.date)
      .tz(utils.getUserTimezone())
      .endOf('day')
      .subtract(1, 'seconds');
    const endDate = moment.min(dateFromDrupal, midnight);
    if (endDate.get('hour') === 0) {
      endDate.subtract(1, 'seconds');
    }
    return endDate.toISOString();
  };

  render() {
    const { availability, date, defaultDate, isClosed, closedMessage, min, max, room } = this.props;
    const startAccessor = this.startDateAccessor('start');
    const endAccessor = this.endDateAccessor('end');

    let events = [];
    const reservations = get(availability, `rooms.${room}.dates`);
    if (reservations) {
      events = map(reservations, (item, key) => ({
        key,
        title: item.message || 'Booked',
        ...item,
      }));
    }

    if (isClosed) {
      events.push({
        start: utils.dateToDrupal(
          moment(date)
            .tz(utils.getUserTimezone())
            .startOf('day')
            .toDate(),
        ),
        end: utils.dateToDrupal(
          moment(date)
            .tz(utils.getUserTimezone())
            .endOf('day')
            .subtract(1, 'seconds')
            .toDate(),
        ),
        allDay: false,
        title: closedMessage,
      });
    }

    return (
      <React.Fragment>
        <BigCalendar
          className={'rbc-calendar--no-overlap'}
          components={components(this.props)}
          date={date}
          defaultDate={defaultDate}
          defaultView={this.props.defaultView}
          elementProps={{
            style: {
              height: 'calc(100vh - 26rem)',
            },
          }}
          eventPropGetter={() => ({
            className: 'rbc-event--disabled',
          })}
          endAccessor={endAccessor}
          events={events}
          getNow={() => utils.getUserTimeNow()}
          max={this.getDateFromTime(max && (max !== '2400' && max !== '0000') ? max : '2359')}
          min={this.getDateFromTime(min || '0000')}
          onNavigate={this.props.onNavigate}
          // onSelectEvent={this.onSelectEvent}
          onView={this.props.onView}
          step={15}
          startAccessor={startAccessor}
          timeslots={4}
          timeZoneName={utils.getUserTimezone()}
          titleAccessor={() => 'Booked'}
          views={['day']}
        />
      </React.Fragment>
    );
  }
}

RoomAvailabilityCalendar.propTypes = {
  onNavigate: PropTypes.func,
  onView: PropTypes.func,
  date: PropTypes.instanceOf(Date),
  defaultDate: PropTypes.instanceOf(Date),
  defaultView: PropTypes.string,
  min: PropTypes.string,
  max: PropTypes.string,
};

RoomAvailabilityCalendar.defaultProps = {
  onNavigate: null,
  onView: null,
  date: utils.getUserStartOfDay(),
  defaultDate: utils.getUserStartOfDay(),
  defaultView: 'day',
  min: '0000',
  max: '2359',
};

export default RoomAvailabilityCalendar;
