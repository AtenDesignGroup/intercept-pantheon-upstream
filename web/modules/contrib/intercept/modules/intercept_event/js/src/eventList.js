import React from 'react';
import { render } from 'react-dom';

/*eslint-disable */
import Drupal from 'Drupal';
import withIntercept from 'intercept/withIntercept';
/* eslint-enable */

import BrowseEventsApp from './components/BrowseEventsApp';

Drupal.behaviors.browseEventsApp = {
  attach: (context) => {
    const App = withIntercept(BrowseEventsApp);
    render(<App />, context.getElementById('eventListRoot'));
  },
};
