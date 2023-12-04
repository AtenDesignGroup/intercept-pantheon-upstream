/**
 * @file
 * Provides jQuery for the Tally default widget.
 */

(function ($, Drupal) {

  /**
   * Provides jQuery for the total field of the Tally default widget.
   */
  Drupal.behaviors.updateTotal = {
    attach: function (context, settings) {

      // If the user changes the counts, let's update the "Total" on the fly.
      // .input-field is a div wrapper around each input.
      // .form-number is a class on each input that we're summing.
      $('.js-tally-container .input-field').on('input', '.form-number:enabled', function() {
        var totalSum = 0;
        $('.js-tally-container  .input-field .form-number:enabled').each(function() {
          var inputVal = $(this).val();
          if ($.isNumeric(inputVal)) {
            totalSum += parseFloat(inputVal);
          }
        });
        // The one that's disabled is the displayed total value.
        $('.js-tally-container .input-field .form-number:disabled').val(totalSum);
      });

    }
  };
  
  })(jQuery, Drupal);
  