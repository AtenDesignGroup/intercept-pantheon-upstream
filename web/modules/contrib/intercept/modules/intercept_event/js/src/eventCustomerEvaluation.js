import React from 'react';
import { render } from 'react-dom';

/* eslint-disable */
import Drupal from 'Drupal';
import drupalSettings from 'drupalSettings';
import withIntercept from 'intercept/withIntercept';
/* eslint-enable */

import EventCustomerEvaluationApp from './components/EventCustomerEvaluationApp';

const App = withIntercept(EventCustomerEvaluationApp);
const user = drupalSettings.intercept.user;

function renderApp(root) {
  const event = root.getAttribute('data-event-uuid');
  const eventType = root.getAttribute('data-event-type-primary-uuid');
  render(<App eventId={event} eventTypeId={eventType} user={user} />, root);
}

Drupal.behaviors.interceptEventCustomerEvaluation = {
  attach: (context) => {
    const roots = [...context.getElementsByClassName('js-event-evaluation--attendee')];
    roots.map(renderApp);
  },
};
