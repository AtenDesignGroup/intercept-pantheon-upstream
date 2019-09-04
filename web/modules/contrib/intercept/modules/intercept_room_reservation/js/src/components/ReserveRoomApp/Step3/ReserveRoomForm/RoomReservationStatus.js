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
    canceled: 'Cancelling reservation',
  },
  syncing: {
    requested: 'Requesting reservation',
    approved: 'Approving reservation',
    denied: 'Denying reservation',
    canceled: 'Cancelling reservation',
  },
  saved: {
    requested: 'This reservation has been requested',
    approved: 'This reservation has been approved',
    denied: 'This reservation has been denied',
    canceled: 'This reservation has been canceled',
  },
  error: {
    requested: 'An error occured while requesting this reservation',
    approved: 'An error occured while approving this reservation',
    denied: 'An error occured while denying this reservation',
    canceled: 'An error occured while cancelling this reservation',
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
