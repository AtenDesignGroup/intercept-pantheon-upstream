import React from 'react';
import { render } from 'react-dom';

/*eslint-disable */
import Drupal from 'Drupal';
import withIntercept from 'intercept/withIntercept';
/* eslint-enable */

import ReserveEquipmentApp from './components/ReserveEquipmentApp';

Drupal.behaviors.myBehavior = {
  attach: (context) => {
    const App = withIntercept(ReserveEquipmentApp);
    render(<App />, context.getElementById('reserveEquipmentRoot'));
  },
};
