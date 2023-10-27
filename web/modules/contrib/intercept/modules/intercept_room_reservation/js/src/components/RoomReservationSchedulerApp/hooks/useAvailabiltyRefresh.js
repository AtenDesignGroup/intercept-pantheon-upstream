import { useEffect, useState } from 'react';
import useAsync from './useAsync';
import useInterval from './useInterval';

const INITIAL_TIMESTAMP = 0;

const fetchRefreshTimestamp = () => {
  return new Promise((resolve, reject) => {
    // Hardcode this view address for prototyping purposes.
    fetch('/api/rooms/availability/refreshed-on')
      .then(res => res.json())
      .then(resolve)
      .catch(reject);
  });
};

/**
 *
 * @param {function} callback
 *   The function to call when availability should be refreshed.
 * @param {number} delay
 *   The number of milliseconds between refreshes.
 * @returns
 *   The timestamp of the last refresh.
 */
export default function useAvailabilityRefresh(callback, delay) {
  const [timestamp, setTimestamp] = useState(INITIAL_TIMESTAMP);

  const { execute, value, error } = useAsync(fetchRefreshTimestamp, false);

  useInterval(() => {
    execute()
      .then(() => {
        if (value && value.refreshed) {
          setTimestamp(value.refreshed);
        }
      })
      .catch(() => {
        console.warn(error);
      });
  }, delay);

  useEffect(() => {
    if (timestamp) {
      callback(timestamp);
    }
  }, [timestamp]);

  return timestamp;
}
