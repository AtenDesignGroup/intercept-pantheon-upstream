import React from 'react';
import { render } from 'react-dom';

/* eslint-disable */
import Drupal from 'Drupal';
import withIntercept from 'intercept/withIntercept';
import interceptClient from 'interceptClient';
/* eslint-enable */

import RoomReservationActionButtonApp from './components/RoomReservationActionButtonApp';

const App = withIntercept(RoomReservationActionButtonApp);

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

Drupal.behaviors.roomReservationActionButtonApp = {
  attach: (context) => {
    const roots = [...context.getElementsByClassName('js--room-reservation-action')];
    roots.map(renderButton);
  },
};
