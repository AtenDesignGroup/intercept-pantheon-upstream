import React from 'react';
import { render } from 'react-dom';

/* eslint-disable */
import withIntercept from 'intercept/withIntercept';
import drupalSettings from 'drupalSettings';
import interceptClient from 'interceptClient';
/* eslint-enable */

import RoomReservationActionButtonApp from './components/RoomReservationActionButtonApp';

const App = withIntercept(RoomReservationActionButtonApp);
const roots = [...document.getElementsByClassName('js--room-reservation-action')];

function renderButton(root) {
  const uuid = root.getAttribute('data-reservation-uuid');
  const status = root.getAttribute('data-status');

  render(<App
    entityId={uuid}
    isStaff={interceptClient.utils.userIsStaff()}
    status={status}
    type={interceptClient.constants.TYPE_ROOM_RESERVATION}
  />, root);
}
roots.map(renderButton);
