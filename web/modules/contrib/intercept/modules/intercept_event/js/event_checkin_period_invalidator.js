/**
 * @file
 * Makes a request to invalidate the cache for event nodes
 * whose check-in period status has changes.
 */

(function () {
  // We simply need to make the request to trigger the check.
  // We don't need the response.
  const xhr = new XMLHttpRequest();
  xhr.open('GET', '/api/event/invalidate-checkin-period', true);
  xhr.send();
})();
