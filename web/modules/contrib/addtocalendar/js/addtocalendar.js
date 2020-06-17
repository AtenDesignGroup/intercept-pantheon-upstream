(function ($) {

  'use strict';

  Drupal.behaviors.addtocalendar = {
    attach: function (context, settings) {
      addtocalendar.load();
    }
  };

})(jQuery);
