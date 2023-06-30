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
      // Note: You may want to remove the start and end date parameters from
      // the query string when navigating to the calendar, otherwise you may
      // get unexpected results.
      urlParams.delete('date_start');
      urlParams.delete('date_end');
      $('.path-events .view-switcher__button, .path-events-calendar .view-switcher__button').off('click').on('click', function(e) {
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
