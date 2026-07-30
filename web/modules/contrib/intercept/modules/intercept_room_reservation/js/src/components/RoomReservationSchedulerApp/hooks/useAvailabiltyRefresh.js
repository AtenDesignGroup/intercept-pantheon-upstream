import { useEffect, useRef } from 'react';

const MAX_DELAY = 320_000;

const fetchRefreshTimestamp = () =>
  fetch('/api/rooms/availability/refreshed-on').then(res => {
    if (!res.ok) {
      throw new Error(`HTTP ${res.status}`);
    }
    return res.json();
  });

/**
 * Polls /api/rooms/availability/refreshed-on and invokes callback when the
 * server-side availability data has changed. Implements:
 *   - Completion-gated polling: the next request only fires after the previous
 *     one settles, so a slow or hung request cannot stack up concurrent calls.
 *   - Exponential backoff on failure: doubles the interval on each consecutive
 *     error (10 s → 20 s → 40 s → … → 320 s max), then resets on success.
 *   - Tab-visibility guard: pauses the loop while the browser tab is hidden
 *     and fires immediately when the tab becomes active again.
 *
 * @param {function} callback
 *   Called with the refreshed timestamp when availability has changed.
 * @param {number} delay
 *   Base poll interval in milliseconds (also the reset value after success).
 */
export default function useAvailabilityRefresh(callback, delay) {
  const callbackRef = useRef(callback);
  const timeoutRef = useRef(null);
  const currentDelayRef = useRef(delay);
  const pendingRef = useRef(false);
  const mountedRef = useRef(true);
  const lastTimestampRef = useRef(null);

  useEffect(() => {
    callbackRef.current = callback;
  }, [callback]);

  // eslint-disable-next-line react-hooks/exhaustive-deps
  useEffect(() => {
    mountedRef.current = true;

    const schedule = (ms) => {
      clearTimeout(timeoutRef.current);
      timeoutRef.current = setTimeout(() => {
        if (document.hidden) {
          // Tab is hidden — pause the loop and wait for visibilitychange.
          pendingRef.current = true;
          return;
        }
        runFetch();
      }, ms);
    };

    const runFetch = () => {
      fetchRefreshTimestamp()
        .then(data => {
          if (!mountedRef.current) return;
          // Success: reset backoff to the base delay.
          currentDelayRef.current = delay;
          if (data && data.refreshed && data.refreshed !== lastTimestampRef.current) {
            lastTimestampRef.current = data.refreshed;
            callbackRef.current(data.refreshed);
          }
        })
        .catch(() => {
          if (!mountedRef.current) return;
          // Failure: double the delay, capped at MAX_DELAY.
          currentDelayRef.current = Math.min(currentDelayRef.current * 2, MAX_DELAY);
        })
        .finally(() => {
          if (!mountedRef.current) return;
          schedule(currentDelayRef.current);
        });
    };

    const onVisibilityChange = () => {
      if (!document.hidden && pendingRef.current) {
        pendingRef.current = false;
        // Fire immediately; backoff state is preserved and determined by outcome.
        clearTimeout(timeoutRef.current);
        runFetch();
      }
    };

    document.addEventListener('visibilitychange', onVisibilityChange);
    schedule(delay);

    return () => {
      mountedRef.current = false;
      clearTimeout(timeoutRef.current);
      document.removeEventListener('visibilitychange', onVisibilityChange);
    };
  }, []);

  /**
   * Fetches the current refreshed-on timestamp and silently updates the
   * internal ref so the next poll does not treat already-fetched data as
   * stale. Call this alongside any direct (non-callback) invocation of the
   * availability fetch so the poller stays in sync.
   */
  const syncTimestamp = () => {
    fetchRefreshTimestamp()
      .then(data => {
        if (!mountedRef.current) return;
        if (data && data.refreshed) {
          lastTimestampRef.current = data.refreshed;
        }
      })
      .catch(() => {}); // best-effort; polling will self-correct
  };

  return { syncTimestamp };
}
