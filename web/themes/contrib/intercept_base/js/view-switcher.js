/**
 * Behaviors for intercept_base view-switcher
 */
(function($, Drupal) {
  Drupal.behaviors.intercept_base_view_switcher = {
    attach: function(context, settings) {
      // Grab the latest search query from window.location.search
      var queryString = window.location.search;
      // Docs: https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams
      var urlParams = new URLSearchParams(queryString);

      // List -> Calendar
      $('.path-events .view-switcher__button').off('click').on('click', function(e) {
        // Note: You may want to remove the start and end date parameters from
        // the query string when navigating to the calendar, otherwise you may
        // get unexpected results.
        if (urlParams.has('date_start')) {
          urlParams.set('start', urlParams.get('date_start'));
          urlParams.delete('date_start');
        } else {
          urlParams.set('start', new Date().toISOString().split('T')[0]);
        }
        urlParams.delete('date_end');

        // Turn off the normal click behavior.
        e.preventDefault();
        // Find the URL of the link the customer clicked on.
        var destination = $(this).attr('href');
        // Create a new destination url
        destination += '?' + urlParams;
        // Navigate to the new destination.
        window.location.href = destination;
      });

      // Calendar -> List
      $('.path-events-calendar .view-switcher__button').off('click').on('click', function(e) {
        if (urlParams.has('start')) {
          urlParams.set('date_start', urlParams.get('start'));
          urlParams.delete('start');
        }

        // Turn off the normal click behavior.
        e.preventDefault();
        // Find the URL of the link the customer clicked on.
        var destination = $(this).attr('href');
        // Create a new destination url
        destination += '?' + urlParams;
        // Navigate to the new destination.
        window.location.href = destination;
      });



    }
  }
})(jQuery, Drupal);
