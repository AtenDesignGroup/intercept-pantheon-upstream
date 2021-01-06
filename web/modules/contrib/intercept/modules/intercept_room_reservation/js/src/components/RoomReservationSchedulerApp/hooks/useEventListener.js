import { useRef, useEffect } from 'react';
import $ from 'jQuery';

// Hook
export default function useEventListener(eventName, handler, element = window, useJQuery = false) {
  // Create a ref that stores handler
  const savedHandler = useRef();

  // Update ref.current value if handler changes.
  // This allows our effect below to always get latest handler ...
  // ... without us needing to pass it in effect deps array ...
  // ... and potentially cause effect to re-run every render.
  useEffect(() => {
    savedHandler.current = handler;
  }, [handler]);

  useEffect(
    () => {
      if (useJQuery && typeof $ !== 'undefined') {
        const eventListener = (event, ...args) => savedHandler.current(event, ...args);

        $(element).on(eventName, eventListener);

        return () => {
          $(element).off(eventName, eventListener);
        };
      }

      // Make sure element supports addEventListener
      // On
      const isSupported = element && element.addEventListener;
      if (!isSupported) return;

      // Create event listener that calls handler function stored in ref
      const eventListener = event => savedHandler.current(event);

      // Add event listener
      element.addEventListener(eventName, eventListener);

      // Remove event listener on cleanup
      return () => {
        element.removeEventListener(eventName, eventListener);
      };
    },
    [eventName, element] // Re-run if eventName or element changes
  );
};
