import React from 'react';
import { render } from 'react-dom';
import AddToCalendar from 'intercept/AddToCalendar';
import moment from 'moment';
/* eslint-disable */
import Drupal from 'Drupal';
import interceptClient from 'interceptClient';
/* eslint-enable */

// This was an attempt to make .ics blobs work on iOS.
// We may need revisit this in the future but for now we are just removing .ics options
// from addToCalendar.
// AddToCalendar.prototype.handleDropdownLinkClick = function (e) {
//   const isIos = !!navigator.platform && /iPad|iPhone|iPod/.test(navigator.platform);
//   if (isIos) {
//     e.target.target = '_self';
//     return true;
//   }
//   handleDropdownLinkClick.call(this, e);
// };

const { utils } = interceptClient;

const getProp = (context, selector) => {
  const el = context.getElementsByClassName(selector);
  return el.length > 0 ? el[0].innerHTML : null;
};

const parseDate = date =>
  moment.tz(date, 'YYYY-MM-DD HH:mm:ss', utils.getUserTimezone()).toISOString();

function renderApp(root) {
  const event = {
    title: getProp(root, 'atc_title'),
    description: getProp(root, 'atc_description').trim(),
    location: getProp(root, 'atc_location'),
    startTime: parseDate(getProp(root, 'atc_date_start')),
    endTime: parseDate(getProp(root, 'atc_date_end')),
    url: window.location.href,
  };

  const services = root.getAttribute('data-calendars') || '';

  const items = services.split(', ').map((item) => {
    switch (item) {
      case 'iCalendar':
        return { apple: 'Apple Calendar' };
      case 'Google Calendar':
        return { google: 'Google' };
      case 'Outlook':
        return { outlook: 'Outlook' };
      case 'Outlook Online':
        return { outlookcom: 'Outlook.com' };
      case 'Yahoo! Calendar':
        return { yahoo: 'Yahoo' };
      default:
        return null;
    }
  });

  render(<AddToCalendar className={'add-to-cal'} event={event} listItems={items} />, root);
}

Drupal.behaviors.interceptEventCustomerEvaluation = {
  attach: (context) => {
    const roots = [...context.getElementsByClassName('addtocalendar')];
    roots.map(renderApp);
  },
};
