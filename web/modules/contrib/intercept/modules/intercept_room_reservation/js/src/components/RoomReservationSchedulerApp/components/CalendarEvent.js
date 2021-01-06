import React from 'react';
import PropTypes from 'prop-types';

const CalendarEvent = ({ event }) => {
  return (
    <div className="reservation-calendar-event reservation-calendar-event">
      <p className="reservation-calendar-event__title">{event.title}</p>
    </div>
  );
};

CalendarEvent.propTypes = {
  event: PropTypes.shape({
    title: PropTypes.string.isRequired,
  }).isRequired,
};

export default CalendarEvent;
