/**
 * Provides supporting functionality for the room reservation forms.
 */
(function ($, Drupal) {
  Drupal.behaviors.roomReservationFormHelper = {
    attach: function (context, settings) {

      
      // Make sure this only loads once.
      // Also, trim whitespace from the user field whenever it changes.
      $('input[id^="edit-field-user-0-target-id"]', context).once().change(function() {
        var cardNumber = $(this).val().trim();
        $(this).val(cardNumber);
      });

      // When changing the start date, make the end date match.
      $('input[id^="edit-field-dates-0-value-date"]', context).once().change(function() {
        var endDate = $('input[id^="edit-field-dates-0-end-value-date"]');
        if ($(this).val() > endDate.val()) {
          endDate.val($(this).val());
          $('input[id^="edit-field-dates-0-end-value-date"]').val($(this).val());
        }
      });


    }
  };
})(jQuery, Drupal);
