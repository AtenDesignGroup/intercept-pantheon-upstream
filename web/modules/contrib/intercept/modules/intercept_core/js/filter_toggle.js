/**
 * @file
 * Provides JavaScript for event list.
 */

(function ($, Drupal) {

/**
 * Provides supporting JS for the event list.
 */
Drupal.behaviors.filterToggle = {
  attach: function (context, settings) {

    // Make the toggle initially checked/on, but only based on config/local settings.
    var initial_state = window.drupalSettings.intercept.events.toggle_filter;
    var state_local = window.localStorage.getItem('toggle_filter');
    if (state_local === null) {
      window.localStorage.setItem('toggle_filter', initial_state);
      state_local = initial_state;
    }
    if ((initial_state === 'expanded' && state_local === null) || state_local === 'expanded') {
      $('#filter_toggle').attr('checked', true);
    }
    else if ((initial_state === 'collapsed' && state_local === null) || state_local === 'collapsed') {
      $('#filter_toggle').attr('checked', false);
      $('.filters__inputs').hide();
    }
    else if (initial_state === 'hidden') {
      $('.filter_toggle label').hide(); // Hide the wrapper div.
    }
    // Toggle everything when it's clicked.
    $('#filter_toggle').on('click', function() {
      $('.filters__inputs').slideToggle(400, function() {
        // Animation complete. Toggle the checkbox.
        $('#filter_toggle').attr('checked', function(index, attr) {
          return attr == true ? false : true;
        });
      });

      // Set the local storage value based on the customer's last selection.
      if (state_local === 'expanded') {
        window.localStorage.setItem('toggle_filter', 'collapsed');
        state_local = 'collapsed';
      }
      else if (state_local === 'collapsed') {
        window.localStorage.setItem('toggle_filter', 'expanded');
        state_local = 'expanded';
      }
    });

  },
};

})(jQuery, Drupal);
