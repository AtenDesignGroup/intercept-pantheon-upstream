/**
 * Provides supporting functionality for the room reservation forms.
 */
(function ($, Drupal) {
  Drupal.behaviors.reservationForMe = {
    attach: function (context, settings) {
      

      // Make sure this only loads once.
      // Now we need to actually change the value of the field_user if the
      // person checked the box.
      $('[data-drupal-selector="edit-reservation-for-me"]', context).once().change(function() {
        var uid = window.drupalSettings.user.uid;
        var username = window.drupalSettings.intercept.user.name;
        var field_user = $('[data-drupal-selector="edit-field-user-0-target-id"]');
        var wrapper = $('[data-drupal-selector="edit-field-user-wrapper"]');
        if ($(this).is(':checked')) {
          // Hide the field_user
          wrapper.slideUp(300, function() {
            // Animation complete.
            // Fill input with the username of the current user.
            field_user.val(username + ' (' + uid + ')');
          });
        }
        else {
          // Clear the value.
          field_user.val('');
          wrapper.slideDown();
        }
      });



    }
  };
})(jQuery, Drupal);