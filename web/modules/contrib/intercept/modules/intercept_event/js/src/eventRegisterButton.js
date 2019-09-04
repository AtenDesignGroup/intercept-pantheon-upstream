import React from 'react';
import { render } from 'react-dom';

/*eslint-disable */
import Drupal from 'Drupal';
import drupalSettings from 'drupalSettings';
import withIntercept from 'intercept/withIntercept';
/* eslint-enable */

import EventRegisterButtonApp from './components/EventRegisterButtonApp';

const App = withIntercept(EventRegisterButtonApp);
const user = drupalSettings.intercept.user;

function renderButton(root) {
  const uuid = root.getAttribute('data-event-uuid');
  render(<App eventId={uuid} user={user} />, root);
}

Drupal.behaviors.eventRegisterButtonApp = {
  attach: (context) => {
    const roots = [...context.getElementsByClassName('js--event-register-button')];
    roots.map(renderButton);
  },
};
