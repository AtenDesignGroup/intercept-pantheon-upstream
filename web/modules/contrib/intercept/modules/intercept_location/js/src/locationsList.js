import React from 'react';
import { render } from 'react-dom';
import withIntercept from 'intercept/withIntercept';
import LocationsList from './components/locationsList';

const App = withIntercept(LocationsList);

render(<App />, document.getElementById('locationsListRoot'));
