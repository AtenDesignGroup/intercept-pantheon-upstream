(function($, Drupal) {
  function qs(search) {
    var a = search.substr(1).split("&");
    if (a == "") return {};
    var b = {};
    for (var i = 0; i < a.length; ++i) {
      var p = a[i].split("=", 2);
      if (p.length == 1) b[p[0]] = "";
      else b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
    }
    return b;
  }

  function getDateValue(search) {
    return qs(search).field_date_time_value;
  }

  function setActiveLink(context) {
    $('[data-drupal-selector="edit-field-date-time-value"]', context).addClass(
      "visually-hidden"
    );

    var current = getDateValue(window.location.search) || "2";
    $(".js-user-events-switcher .view-switcher__button")
      .removeClass("view-switcher__button--active")
      .filter(`[href="?field_date_time_value=${current}"]`)
      .addClass("view-switcher__button--active");
  }

  $(document).ready(function() {
    $("body").on(
      "click",
      ".js-user-events-switcher .view-switcher__button",
      function(e) {
        // e.preventDefault();
        var current = getDateValue($(e.currentTarget).attr("href"));
        $(`input[value="${current}"][name="field_date_time_value"]`)
          .prop("checked", true)
          .trigger("change");
      }
    );

    // Wrap each image select with an extra span tag for use in CSS.
    $('img.image_picker_image').wrap('<span class="MuiIconButton-label"></span>');
    // Add some legacy classes.
    $('.image_picker_selector').addClass('MuiFormGroup-root evaluation__widget-inputs');
    $('.evaluation .thumbnail').addClass('MuiButtonBase-root MuiIconButton-root jss1 MuiRadio-root evaluation__radio-icon jss2');
    $('.evaluation .thumbnail.selected').addClass(' Mui-checked evaluation__radio-icon--checked');
    $('.webform-image-select').change(function() {
      $('.evaluation .thumbnail').each(function() {
        if (!$(this).hasClass('selected')) {
          $(this).removeClass('Mui-checked evaluation__radio-icon--checked');
        }
        else {
          $(this).addClass('Mui-checked evaluation__radio-icon--checked');
        }
      });
    });

  });

  Drupal.behaviors.userEventSetActiveLink = {
    attach: setActiveLink
  };
})(jQuery, Drupal);
