// React
import React from 'react';
import { render } from 'react-dom';

// Intercept
/* eslint-disable */
import withIntercept from 'intercept/withIntercept';
/* eslint-enable */


import ReserveRoomApp from './components/ReserveRoomApp';

const App = withIntercept(ReserveRoomApp);

render(<App />, document.getElementById('reserveRoomRoot'));
