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
      $('.js-tally-container').once('js-tally-attached').each(function() {
        var $container = $(this);
        $container.on('input', '.js-tally-input', function() {
          var totalSum = 0;
          $('.js-tally-input', $container).each(function() {
            var inputVal = $(this).val();
            if ($.isNumeric(inputVal)) {
              totalSum += parseFloat(inputVal);
            }
          });
          $('.js-tally-total', $container).val(totalSum);
        });
      });

    }
  };

  })(jQuery, Drupal);
