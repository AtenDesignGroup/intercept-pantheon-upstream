(function ($, Drupal) {
  $(document).ready(function() {

    // logic for the logged in greeting in block--useraccountmenu.html.twig
    const hour = new Date().getHours();
    let salutation;
    if (hour < 12) {
      salutation = 'Good morning';
    } else if (hour < 17) {
      salutation = 'Good afternoon';
    } else {
      salutation = 'Good evening';
    }
    $('#salutation').text(Drupal.t(salutation));

    // Remove anchors from menu items with "no-link" class.
    $(".region--primary-menu ul.menu li a.no-link").each(function() {
      const textContent = document.createElement("span");
      const classes = $(this).attr('class').split(/\s+/);
      $.each(classes, function(index, item) {
        textContent.classList.add(item);
      });

      textContent.innerHTML = $(this).html();
      const parent = $(this).parent();
      parent.find("a").remove();
      parent.append(textContent);
    });

  });
})(jQuery, Drupal);
