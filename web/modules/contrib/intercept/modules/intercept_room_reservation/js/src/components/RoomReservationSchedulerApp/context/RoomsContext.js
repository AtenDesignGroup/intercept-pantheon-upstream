/**
 *  Context: Rooms
 */

import React, { useEffect, useState, useCallback } from 'react';
import PropTypes from 'prop-types';
import get from 'lodash/get';
import useAsync from '../hooks/useAsync';
import useEventListener from '../hooks/useEventListener';

// Create a Context
const RoomsContext = React.createContext();

const fetchRooms = uri => () => {
  return new Promise((resolve, reject) => {
    // Hardcode this view address for prototyping purposes.
    fetch(`${uri}&fields[node--room]=title,field_location,drupal_internal__nid`, {
      credentials: 'same-origin',
    })
      .then(res => res.json())
      .then(resolve)
      .catch(reject);
  });
};

export const RoomsProvider = ({ view, display, children }) => {
  const [uri, setUri] = useState(null);

  const fetch = useCallback(fetchRooms(uri), [uri]);

  const { execute, pending, value, error } = useAsync(fetch, false);

  // Event handler utilizing useCallback ...
  // ... so that reference never changes.
  const handler = useCallback(
    ({ detail }) => {
      if (view === detail.view && display === detail.display) {
        // Update uri
        setUri(detail.uri);
      }
    },
    [setUri],
  );

  // Listen for changes to the View exposed filters.
  useEventListener('jsonApiViewsUriChange', handler);

  // If the uri changes, make a request.
  useEffect(() => {
    const defaultUri = get(window, 'Drupal.behaviors.jsonApiViewsBlock.getUri')
      ? window.Drupal.behaviors.jsonApiViewsBlock.getUri(view, display)
      : null;

    setUri(defaultUri);
  }, []);

  // If the uri changes, make a request.
  useEffect(() => {
    if (uri) execute();
  }, [uri]);

  const data = {
    rooms: value ? value.data : [],
    pending,
    error,
  };

  return (
    <RoomsContext.Provider value={data}>
      {children}
    </RoomsContext.Provider>
  );
};

RoomsProvider.propTypes = {
  children: PropTypes.element.isRequired,
  display: PropTypes.string.isRequired,
  view: PropTypes.string.isRequired,
};

export default RoomsContext;
