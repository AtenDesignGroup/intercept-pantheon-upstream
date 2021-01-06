import React from 'react';
import PropTypes from 'prop-types';

export const CalendarWeekEvent = ({ event }) => {
  return (
    <div className="reservation-calendar-event reservation-calendar-event">
      <p className="reservation-calendar-event__user">{'User Name'}</p>
      <p className="reservation-calendar-event__title">{event.title}</p>
    </div>
  );
};

CalendarWeekEvent.PropTypes({
  event: PropTypes.shapeOf({
    title: PropTypes.string,
  }),
});

export default CalendarWeekEvent;
