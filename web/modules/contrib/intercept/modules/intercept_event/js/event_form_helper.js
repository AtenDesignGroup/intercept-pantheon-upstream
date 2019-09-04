/**
 * @file
 * Provides JavaScript for node edit/add forms.
 */

(function ($, Drupal) {

/**
 * Provides supporting JS for the node edit/add event forms.
 */
Drupal.behaviors.eventFormHelper = {
  attach: function (context, settings) {

    var $root = this;
    $('#edit-field-date-time-0-value-date', context).once().change(function() {
      var endDate = $('#edit-field-date-time-0-end-value-date');
      if ($(this).val() > endDate.val()) {
        endDate.val($(this).val());
        $('[name="field_date_time[0][end_value][date]"]').val($(this).val());
      }
    });

    // Make dependent/conditional field (field_presenter) appear/disappear based
    // on whether the boolean field is checked/unchecked.
    var $nonStaff = $("#edit-field-presented-by-non-staff-value");
    var $presenter = $('.form-item-field-presenter-0-value');
    $presenter.css('padding', '20px');
    if (!$nonStaff.is(":checked")) {
      $presenter.hide();
    }
    $nonStaff.change(function() {
      if (this.checked) {
        $presenter.slideDown();
        var $elements = $presenter.addClass('teal lighten-5');
        setTimeout(function() {
          $elements.removeClass('teal lighten-5')
        }, 4000); // 4 seconds
      }
      else {
        $presenter.slideUp();
        $('#edit-field-presenter-0-value').val('');
      }

    });
    

  },
};

})(jQuery, Drupal);
