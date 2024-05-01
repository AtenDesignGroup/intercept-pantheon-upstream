(function ($, Drupal, once) {
  /**
   * Provides jQuery for the total field of the Tally default widget.
   */
  Drupal.behaviors.updateTotal = {
    attach: function (context, settings) {

      // If the user changes the counts, let's update the "Total" on the fly.
      once('js-tally-attached', '.js-tally-container').forEach(function(element) {
        var $container = $(element);
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
  })(jQuery, Drupal, once);
