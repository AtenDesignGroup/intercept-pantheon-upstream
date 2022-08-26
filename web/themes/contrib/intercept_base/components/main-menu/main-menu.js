/*
  main-menu.js
 */

(function ($, Drupal, tabbed) {
  Drupal.behaviors.mainMenu = {
    attach: function (context) {
      $('.header__site-navigation').once('mainMenu').each(function (index, element) {
        var mainMenuTabs = new tabbed(`mainMenuTabs-${index}`);

        mainMenuTabs.init(
          $(element).find('.js-main-menu__tab'),
          $(element).find('.js-main-menu__panel'),
          {
            deselectAll: true,
            hover: false,
            container: '.header__site-navigation',
          }
        );
      })

      // Collapse all tabs when a toggle is expanded.
      $('body').on('expand.toggle', '.js-toggle', function () {
        $('.js-main-menu__tab').trigger('deselect');
      });

      // Collapse all dropdowns when a tab is expanded.
      $('body').on('click', '.js-main-menu__tab, .slide-menu-toggle', function () {
        $('[data-toggleGroup="header"]').trigger('collapse');
      });

      // Collapse all dropdowns when a manage account trigger is clicked.
      $('body').on('click', '[for="slide-menu-toggle"]', function () {
        $('[data-toggleGroup="header"]').trigger('collapse');
      });
    },
  };
})(jQuery, Drupal, tabbed);
