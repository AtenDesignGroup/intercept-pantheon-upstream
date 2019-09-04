(function ($) {

  $(document).ready(function () {

    $('.js-mobile-nav-trigger').on('change', e => {
      $('body')[e.target.checked ? 'addClass' : 'removeClass']('js-prevent-scroll');
      $(this)[e.target.checked ? 'addClass' : 'removeClass']('checked');
    });

    $(document).click(function(e) { 
      if(!$(e.target).closest('.header__utilities, .header__menu-main, .header__site-search').length) {
        $('.js-mobile-nav-trigger').prop('checked', false);
      }
    });

    // TODO: Combine with ^ above function
    $('.js-mobile-nav-trigger').on('change', function() {
      // Uncheck (toggle) other mobile dropdowns
      $(this).parent().siblings().children('.js-mobile-nav-trigger').prop('checked', false);
    });

  });

})(jQuery);
