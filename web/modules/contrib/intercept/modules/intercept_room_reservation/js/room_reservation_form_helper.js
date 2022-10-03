/**
 * Provides supporting functionality for the room reservation forms.
 */
(function ($, Drupal) {
  Drupal.behaviors.roomReservationFormHelper = {
    attach: function (context, settings) {

      
      // Make sure this only loads once.
      // Also, trim whitespace from the user field whenever it changes.
      $("input[name='field_user[0][target_id]'", context).once().change(function() {
        var cardNumber = $(this).val().trim();
        $(this).val(cardNumber);
      });


    }
  };
})(jQuery, Drupal);
