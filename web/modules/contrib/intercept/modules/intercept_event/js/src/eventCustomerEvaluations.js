import React from 'react';
import { render } from 'react-dom';

/* eslint-disable */
import Drupal from 'Drupal';
import withIntercept from 'intercept/withIntercept';
/* eslint-enable */

import EventCustomerEvaluationsApp from './components/EventCustomerEvaluationsApp';

const App = withIntercept(EventCustomerEvaluationsApp);

function renderApp(root) {
  const event = root.getAttribute('data-event-uuid');
  render(<App eventId={event} />, root);
}

Drupal.behaviors.interceptEventCustomerEvaluation = {
  attach: (context) => {
    const roots = [...context.getElementsByClassName('js-event-evaluations--attendee')];
    roots.map(renderApp);
  },
};
