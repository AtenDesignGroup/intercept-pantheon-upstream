(function ($) {

  $(document).ready(function () {

    // scroll to top functionality
    const topBtn = $('#scroll-to-top');

    $(window).scroll(function() {
      const scrollPos = $(window).scrollTop();

      if (scrollPos > 2000) {
        topBtn.fadeIn();
      } else {
        topBtn.fadeOut();
      }
    });

    topBtn.click(function() {
      console.log('clicked');
      $('html, body').animate({ scrollTop: '0px' }, 800);
    });

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
