/*
  header-mobile.js
 */

(function ($, Drupal, tabbed) {
  Drupal.behaviors.headerMobile = {
    attach: function (context) {

      $('.js-header-mobile', context).once('headerMobile').each(function () {
        const headerMobileTabs = new tabbed('headerMobileTabs');

        // Prevent scrolling when the mobile menu is open.
        const $body = $('body');
        $body.on({
          'show.headerMobileTabs': function () {
            $body.addClass('js-prevent-scroll');
          },
          'hide.headerMobileTabs': function () {
            $body.removeClass('js-prevent-scroll');
          }
        }, '.js-mobile-panel');

        headerMobileTabs.init(
          $('.js-mobile-tab'),
          $('.js-mobile-panel'),
          {
            deselectAll: true,
            hover: false,
            container: '.js-header-mobile',
          }
        );
      })
    },
  };
})(jQuery, Drupal, tabbed);
