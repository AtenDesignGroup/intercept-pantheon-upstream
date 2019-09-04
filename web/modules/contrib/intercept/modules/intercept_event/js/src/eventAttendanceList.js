import React from 'react';
import { render } from 'react-dom';
import withIntercept from 'intercept/withIntercept';
import drupalSettings from 'drupalSettings';
import EventAttendanceListApp from './components/EventAttendanceListApp';

const App = withIntercept(EventAttendanceListApp);
const root = document.getElementById('eventAttendanceListRoot');
const user = drupalSettings.intercept.user;

const uuid = root.getAttribute('data-event-uuid');
const nid = root.getAttribute('data-event-nid');
render(<App event={{ uuid, nid }} user={user} />, root);
