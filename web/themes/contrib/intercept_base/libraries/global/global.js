(function ($) {

  $(document).ready(function () {

    // Add your code here

  });

})(jQuery);

(function ($, Drupal) {
  /**
   * Provides JS helper functions for user settings form.
   */
  Drupal.behaviors.materializeInitRichland = {
    attach: function attach(context, settings) {
      $('.views-exposed-form select').formSelect();
    },
  };
})(jQuery, Drupal);
