/**
 *  Context: Calendar
 */

import React from 'react';
import PropTypes from 'prop-types';
import EventWrapper from '../components/EventWrapper';
import EventContainerWrapper from '../components/EventContainerWrapper'

// Create a Context
const CalendarContext = React.createContext();

export const CalendarProvider = ({ children, ...props }) => {
  const value = props;

  // Swap out Drag and Drop components.
  if (props.dnd) {
    value.components.eventWrapper = EventWrapper;
    value.components.eventContainerWrapper = EventContainerWrapper;
  }

  return (
    <CalendarContext.Provider value={value}>
      {children}
    </CalendarContext.Provider>
  );
};

CalendarProvider.propTypes = {
  children: PropTypes.element.isRequired,
  dnd: PropTypes.bool,
};

CalendarProvider.defaultProps = {
  dnd: false,
}
export default CalendarContext;
