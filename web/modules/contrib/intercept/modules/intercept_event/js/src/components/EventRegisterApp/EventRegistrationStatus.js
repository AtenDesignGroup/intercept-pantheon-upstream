import React from 'react';
import PropTypes from 'prop-types';
import interceptClient from 'interceptClient';
import EntityStatus from 'intercept/EntityStatus';

const c = interceptClient.constants;

const messages = {
  default: 'The status of the registration is unknown.',
  dirty: {
    waitlist: 'Submitting registration to the waitlist',
    active: 'Submitting registration',
    canceled: 'Canceling registration',
  },
  syncing: {
    waitlist: 'Submitting registration to the waitlist',
    active: 'Submitting registration',
    canceled: 'Canceling registration',
  },
  saved: {
    waitlist: 'This registration has been added to the waitlist.',
    active: 'This registration has been confirmed.',
    canceled: 'This registration has been canceled.',
  },
  error: {
    waitlist: 'An error occured while adding to the waitlist.',
    active: 'An error occured while submitting this registration.',
    canceled: 'An error occured while canceling this registration.',
  },
};

const EventRegistrationStatus = props => (
  <EntityStatus
    type={c.TYPE_EVENT_REGISTRATION}
    id={props.uuid}
    messages={messages}
    statusPath={'data.attributes.status'}
  />
);

EventRegistrationStatus.propTypes = {
  uuid: PropTypes.string.isRequired,
};

export default EventRegistrationStatus;
