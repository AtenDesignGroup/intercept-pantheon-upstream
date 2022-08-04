function getDelay(initial, step, jitter) {
  return Math.floor(initial * (2 ** step) * (1 + (jitter * ((Math.random() * 2) - 1))));
}

export default function exponentialBackoff(
  callable,
  max = 8,
  initial = 200,
  jitter = 0.2
) {
  return new Promise((resolve, reject) => {
    let tries = 0;
    const caller = () => {
      const delay = getDelay(initial, tries, jitter);

      callable()
        .then(res => {
          resolve(res);
        })
        .catch(err => {
          if (tries >= max) {
            reject(err);
          }
          else {
            tries += 1;
            setTimeout(() => {
              caller();
            }, delay);
          }
        });
    };
    caller();
  });
}

/**
 * Normalizes the resolution and rejection of a fetch request.
 * @param  {Request} request The request object. https://developer.mozilla.org/en-US/docs/Web/API/Request
 * @param  {responseHandlerCallback} responseHandler - The callback that handles the response.
 * @param  {errorHandlerCallback} errorHandler - The callback that handles the error from a failed request.
 * @return {Promise}         A promise that will resolve if the fetch response is OK. It will reject otherwise.
 */
export function wrapFetch(request, responseHandler, errorHandler) {
  return () => new Promise((resolve, reject) => {
    // Fetch the request.
    fetch(request.clone())
    // Handle a successful request.
      .then(responseHandler(request, resolve, reject))
      .catch(errorHandler(request, resolve, reject));
  });
}

/**
 * Retries a failed request using Exponential Backoff.
 * @param  {Request} request The request object. https://developer.mozilla.org/en-US/docs/Web/API/Request
 * @param  {responseHandlerCallback} responseHandler - The callback that handles the response.
 * @param  {errorHandlerCallback} errorHandler - The callback that handles the error from a failed request.
 * @return {Promise}  A wrapped fetch request that will retry if it encounters a failure.
 */
export function backoffFetch(request, responseHandler, errorHandler) {
  return exponentialBackoff(wrapFetch(request, responseHandler, errorHandler));
}
