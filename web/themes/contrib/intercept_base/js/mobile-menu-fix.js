(function($, Drupal) {
  $(document).ready(function() {


    $('.js-mobile-tab:contains("Expand Account")').on('click', function() {
      // Code to execute when the element is clicked
      $('#mobile-panel--account').attr("aria-hidden", function (i, attr) {
        return attr === "true" ? "false" : "true";
      });
    });

    $('.js-mobile-tab:contains("Expand Menu")').on('click', function() {
      // Code to execute when the element is clicked
      $('#mobile-panel--menu').attr("aria-hidden", function (i, attr) {
        return attr === "true" ? "false" : "true";
      });
    });


  });
})(jQuery, Drupal);