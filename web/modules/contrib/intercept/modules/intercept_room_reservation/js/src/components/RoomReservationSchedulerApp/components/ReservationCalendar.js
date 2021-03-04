import React from 'react';
import PropTypes from 'prop-types';

import BigCalendar from 'intercept/BigCalendar';
// import withDragAndDrop from 'react-big-calendar/lib/addons/dragAndDrop';

import Toolbar from 'react-big-calendar/lib/Toolbar';
import { Navigate } from 'react-big-calendar';
import { Button, IconButton } from '@material-ui/core';
import { withStyles } from '@material-ui/core/styles';
import { DatePicker, MuiPickersUtilsProvider } from '@material-ui/pickers';
import ArrowBack from '@material-ui/icons/ArrowBack';
import ArrowForward from '@material-ui/icons/ArrowForward';
import CalendarIcon from '@material-ui/icons/CalendarToday';
import findIndex from 'lodash/findIndex';
import interceptClient from 'interceptClient';
import moment from 'moment';
import MomentUtils from '@date-io/moment';
import CalendarEvent from './CalendarEvent';
import SchedulerView from './SchedulerView';


// const DragAndDropCalendar = withDragAndDrop(BigCalendar);

const { utils } = interceptClient;

class CustomToolbar extends Toolbar {
  render() {
    const { localizer: { messages }, label, maxDate, minDate, date, onDateChange } = this.props;

    // Get the calendar's local date in ISO Date format.
    // Used for min/max comparisons later.
    const ISOdate = moment(date).format('YYYY-MM-DD');

    const onChange = changedDate => onDateChange(changedDate.toDate());

    const HeadingButton = withStyles({
      root: {
        fontSize: '16px',
        letterSpacing: 'normal',
        textTransform: 'none',
      },
    })(Button);

    return (
      <div className="rbc-toolbar">
        <span className="rbc-btn-group">
          <Button
            variant="contained"
            color="primary"
            size="small"
            className="rbc-btn rbc-btn--today"
            onClick={this.navigate.bind(null, Navigate.TODAY)}
          >
            {messages.today}
          </Button>
        </span>
        <div className="rbc-toolbar__heading">
          <IconButton
            className={'rbc-toolbar__pager-button rbc-toolbar__pager-button--prev'}
            onClick={this.navigate.bind(null, Navigate.PREVIOUS)}
            color="primary"
            aria-label="Previous"
            variant="flat"
            disabled={minDate && minDate >= ISOdate}
          >
            <ArrowBack />
          </IconButton>
          <MuiPickersUtilsProvider utils={MomentUtils} moment={moment}>
            <DatePicker
              onChange={onChange}
              TextFieldComponent={({ onClick }) => (
                <HeadingButton
                  name={name}
                  input={date}
                  onClick={onClick}
                  endIcon={<CalendarIcon />}
                >
                  <h2 className="rbc-toolbar__label">{label}</h2>
                </HeadingButton>
              )}
              value={date}
              className="date-filter"
              minDate={minDate}
            />
          </MuiPickersUtilsProvider>
          <IconButton
            className={'rbc-toolbar__pager-button rbc-toolbar__pager-button--next'}
            onClick={this.navigate.bind(null, Navigate.NEXT)}
            color="primary"
            aria-label="Next"
            disabled={maxDate && maxDate <= ISOdate}
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

/**
 * Applies the current selectedEvent values to the corresponding event
 * in the events array.
 *
 * @param {Object[]} events
 *  The list of events.
 * @param {Object} selectedEvent
 *  The selected event values.
 */
function applySelectedEvent(events, selectedEvent) {
  if (!selectedEvent) {
    return events;
  }

  if (typeof selectedEvent.id !== 'string') {
    return [].concat(events, selectedEvent);
  }

  const index = findIndex(events, event => event.id === selectedEvent.id);

  if (index < 0) {
    return events;
  }

  const appliedEvents = [...events];
  appliedEvents[index] = selectedEvent;

  return appliedEvents;
}

class RoomReservationCalendar extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      showEvent: false,
    };

    this.onHideEvent = this.onHideEvent.bind(this);
  }

  onHideEvent() {
    this.setState({
      showEvent: false,
    });
  }

  getDateFromTime = (time, date) => {
    const hours = parseInt(time.slice(0, 2), 10);
    const minutes = parseInt(time.slice(2), 10);
    const dateClone = new Date((date || new Date('Jan 1, 2000 00:00:00')).getTime());
    dateClone.setHours(hours);
    dateClone.setMinutes(minutes);

    return dateClone;
  };

  handleDateChange = (value) => {
    this.props.onDateChange(value);
  };

  handleViewChange = (value) => {
    this.props.onViewChange(value);
    // this.setFetchers(this.props.filters, value, this.props.calView, this.props.date);
  };

  getMergedEvents = (events, selected) => {
    if (!selected || typeof selected.id === 'string') {
      return events;
    }

    return [].concat(events, selected);
  };

  render() {
    const {
      date,
      defaultDate,
      // isClosed,
      // closedMessage,
      min,
      max,
      events,
      resources,
      selected
    } = this.props;

    // if (isClosed) {
    //   events.push({
    //     start: utils.dateToDrupal(
    //       moment(date)
    //         .tz(utils.getUserTimezone())
    //         .startOf('day')
    //         .toDate(),
    //     ),
    //     end: utils.dateToDrupal(
    //       moment(date)
    //         .tz(utils.getUserTimezone())
    //         .endOf('day')
    //         .subtract(1, 'seconds')
    //         .toDate(),
    //     ),
    //     allDay: false,
    //     title: closedMessage,
    //   });
    // }

    // const mergedEvents = this.getMergedEvents(events, selected);

    return (
      <React.Fragment>
        <BigCalendar
          className={'rbc-calendar--no-overlap'}
          components={components(this.props)}
          date={date}
          dnd
          defaultDate={defaultDate}
          defaultView={this.props.view}
          draggableAccessor={this.props.draggableAccessor}
          elementProps={{
            style: {
              height: 'calc(100vh - 26rem)',
            },
          }}
          endAccessor={this.props.endAccessor}
          eventPropGetter={event => ({
            className: `rbc-event--${event.status} ${event.hasEvent ? 'rbc-event--hasEvent' : ''} ${event.isReservedByStaff ? 'rbc-event--isReservedByStaff' : ''}`,
          })}
          events={applySelectedEvent(events, selected)}
          resources={this.props.view === 'day' ? resources : undefined}
          max={this.getDateFromTime(max && max !== '2400' && max !== '0000' ? max : '2359', date)}
          min={this.getDateFromTime(min || '0000', date)}
          onNavigate={this.props.onDateChange}
          onView={this.handleViewChange}
          onEventDrop={this.props.onChangeEvent}
          onEventResize={this.props.onChangeEvent}
          onSelectEvent={this.props.onSelectEvent}
          onDoubleClickEvent={this.props.onDoubleClickEvent}
          onSelectSlot={this.props.onSelectSlot}
          onDragStart={this.props.onDragStart}
          resizable
          selected={selected}
          selectable
          startAccessor={this.props.startAccessor}
          step={15}
          timeslots={4}
          timeZoneName={utils.getUserTimezone()}
          titleAccessor={() => 'Booked'}
          views={{
            day: SchedulerView,
          }}
        />
      </React.Fragment>
    );
  }
}

RoomReservationCalendar.propTypes = {
  onChangeEvent: PropTypes.func,
  onDateChange: PropTypes.func,
  onDragStart: PropTypes.func,
  onDoubleClickEvent: PropTypes.func,
  onSelectEvent: PropTypes.func,
  draggableAccessor: PropTypes.func,
  onSelectSlot: PropTypes.func,
  onViewChange: PropTypes.func,
  endAccessor: PropTypes.func,
  startAccessor: PropTypes.func,
  date: PropTypes.instanceOf(Date),
  defaultDate: PropTypes.instanceOf(Date),
  view: PropTypes.string,
  min: PropTypes.string,
  max: PropTypes.string,
};

RoomReservationCalendar.defaultProps = {
  onChangeEvent: () => {},
  onDateChange: () => {},
  onDoubleClickEvent: () => {},
  onDragStart: () => {},
  onSelectEvent: () => {},
  draggableAccessor: () => {},
  startAccessor: () => {},
  endAccessor: () => {},
  onSelectSlot: () => {},
  onNavigate: null,
  onView: null,
  onViewChange: null,
  date: utils.getUserTimeNow(),
  defaultDate: utils.getUserTimeNow(),
  view: 'day',
  min: '0000',
  max: '2359',
};

export default RoomReservationCalendar;
