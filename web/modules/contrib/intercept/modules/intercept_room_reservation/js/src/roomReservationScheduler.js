// React
import React from 'react';
import { render } from 'react-dom';

// Intercept
/* eslint-disable */
import withIntercept from 'intercept/withIntercept';
/* eslint-enable */


import RoomReservationSchedulerApp from './components/RoomReservationSchedulerApp';

const App = withIntercept(RoomReservationSchedulerApp);

const root = document.getElementById('roomReservationSchedulerRoot');
const viewsId = root.getAttribute('data-jsonapi-views-view');
const displayId = root.getAttribute('data-jsonapi-views-display');

render(<App viewsId={viewsId} displayId={displayId} />, root);
