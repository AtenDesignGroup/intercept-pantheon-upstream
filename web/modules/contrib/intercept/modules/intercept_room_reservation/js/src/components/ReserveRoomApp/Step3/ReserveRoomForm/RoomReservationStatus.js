import React from 'react';
import PropTypes from 'prop-types';
import interceptClient from 'interceptClient';
import EntityStatus from 'intercept/EntityStatus';

const c = interceptClient.constants;

const messages = {
  default: 'The status of the reservation is unknown',
  dirty: {
    requested: 'Requesting reservation',
    approved: 'Approving reservation',
    denied: 'Denying reservation',
    canceled: 'Canceling reservation',
  },
  syncing: {
    requested: 'Requesting reservation',
    approved: 'Approving reservation',
    denied: 'Denying reservation',
    canceled: 'Canceling reservation',
  },
  saved: {
    requested: 'This reservation has been requested',
    approved: 'This reservation has been approved',
    denied: 'This reservation has been denied',
    canceled: 'This reservation has been canceled',
  },
  error: {
    requested: 'An error occurred while requesting this reservation',
    approved: 'An error occurred while approving this reservation',
    denied: 'An error occurred while denying this reservation',
    canceled: 'An error occurred while canceling this reservation',
  },
};

const RoomReservationStatus = props => (
  <EntityStatus
    type={c.TYPE_ROOM_RESERVATION}
    id={props.uuid}
    messages={messages}
    statusPath={'data.attributes.field_status'}
  />
);

RoomReservationStatus.propTypes = {
  uuid: PropTypes.string.isRequired,
};

export default RoomReservationStatus;
