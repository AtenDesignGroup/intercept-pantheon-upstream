import React, { Component } from 'react';
import PropTypes from 'prop-types';
import BigCalendar from 'react-big-calendar';
import moment from 'moment';

BigCalendar.setLocalizer(BigCalendar.momentLocalizer(moment));

const dateAccessor = prop => item =>
  moment(`${item.data.attributes.field_date_time[prop]}Z`, moment.ISO_8601).toDate();
const startAccessor = dateAccessor('value');
const endAccessor = dateAccessor('end_value');
const titleAccessor = item => (
  <p className="calendar-event-title--tiny">{item.data.attributes.title}</p>
);

const CalendarRoom = (props) => {
  const { event } = props;
  const { data } = event;

  return (
    <div className="calendar-event">
      <p className="calendar-event__title">{data.attributes.title}</p>
    </div>
  );
};

const components = {
  event: CalendarRoom,
};

const onSelectRoom = (event) => {
  const url = event.data.attributes.path ? event.data.attributes.path.alias : `/node/${event.data.attributes.nid}`;
  window.location.href = url;
};

const RoomCalendar = props => (
  <BigCalendar
    components={components}
    events={props.events}
    onSelectRoom={onSelectRoom}
    titleAccessor={titleAccessor}
    startAccessor={startAccessor}
    endAccessor={endAccessor}
    onNavigate={props.onNavigate}
    onView={props.onView}
    defaultView={props.defaultView}
    defaultDate={props.defaultDate}
    popup
    views={['month', 'week', 'day']}
    elementProps={{
      style: {
        height: 'calc(100vh - 26rem)',
      },
    }}
    min={new Date('Jan 1, 2000 07:00:00')}
    max={new Date('Jan 1, 2000 22:00:00')}
  />
);

RoomCalendar.propTypes = {
  events: PropTypes.arrayOf(Object).isRequired,
  onNavigate: PropTypes.func,
  onView: PropTypes.func,
  defaultDate: PropTypes.instanceOf(Date),
  defaultView: PropTypes.string,
};

RoomCalendar.defaultProps = {
  onNavigate: null,
  onView: null,
  defaultDate: new Date(),
  defaultView: 'month',
};

export default RoomCalendar;
