// Used to make each "What's New" item on the home page clickable (instead of having duplicate links on image and title)
(function ($) {
  $('body').on('click.blockLink', '.js--block-link', function() {
    var href = $(this).attr('data-href');
    if (href) {
      window.location.href = href;
    }
  });
})(jQuery);
