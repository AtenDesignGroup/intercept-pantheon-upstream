import React from 'react';
import connectQueryParams from './connectQueryParams';
import BrowseEvents from './BrowseEvents';

const params = new URLSearchParams(window.location.search);
const scroll = parseInt(params.get('page'), 10) || 0;

function BrowseEventsApp(props) {
  return (
    <BrowseEvents {...props} scroll={scroll} />
  );
}

export default connectQueryParams(BrowseEventsApp);
