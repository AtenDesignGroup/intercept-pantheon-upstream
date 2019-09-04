import React from 'react';
import { render } from 'react-dom';
import withIntercept from 'intercept/withIntercept';
import RoomReservationListApp from './components/RoomReservationListApp';

const App = withIntercept(RoomReservationListApp);

// render(<App />, document.getElementById('roomReservationsRoot'));
