import React from 'react';
import { render } from 'react-dom';
import withIntercept from 'intercept/withIntercept';
import drupalSettings from 'drupalSettings';
import EventRegisterApp from './components/EventRegisterApp';

const App = withIntercept(EventRegisterApp);
const root = document.getElementById('eventRegisterRoot');
const uuid = root.getAttribute('data-uuid');
const user = drupalSettings.intercept.user;
render(<App eventId={uuid} user={user} />, root);
