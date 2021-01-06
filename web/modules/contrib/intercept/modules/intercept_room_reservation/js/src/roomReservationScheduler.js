// React
import React from 'react';
import { render } from 'react-dom';

// Intercept
/* eslint-disable */
import withIntercept from 'intercept/withIntercept';
/* eslint-enable */


import RoomReservationSchedulerApp from './components/RoomReservationSchedulerApp';

const App = withIntercept(RoomReservationSchedulerApp);

render(<App />, document.getElementById('roomReservationSchedulerRoot'));
