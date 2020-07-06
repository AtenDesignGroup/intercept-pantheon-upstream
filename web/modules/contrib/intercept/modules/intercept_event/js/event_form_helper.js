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
    var $nonStaff = $('#edit-field-presented-by-non-staff-value');
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

    // Make registration fields appear/disappear based on Registration Required
    // checkbox.
    var $registrationRequired = $('#edit-field-must-register-value');
    $('#edit-field-event-user-reg-max-wrapper').addClass('registration-child');
    $('#edit-field-capacity-max-wrapper').addClass('registration-child');
    $('#edit-field-event-register-period-wrapper').addClass('registration-child');
    $('#edit-field-has-waitlist-wrapper').addClass('registration-child');
    $('#edit-field-waitlist-max-wrapper').addClass('registration-child');
    var $registrationChildren = $('.registration-child');

    if (!$registrationRequired.is(":checked")) {
      $registrationChildren.hide();
    }
    else {
      $('#edit-field-event-register-period-0 h4.label').addClass('form-required');
    }

    $registrationRequired.change(function() {
      if (this.checked) {
        $registrationChildren.slideDown();
        var $elements = $registrationChildren.addClass('teal lighten-5');
        setTimeout(function() {
          $elements.removeClass('teal lighten-5')
        }, 4000); // 4 seconds
        $('#edit-field-event-register-period-0 h4.label').addClass('form-required');
      }
      else {
        $registrationChildren.slideUp();
        $('#edit-field-event-register-period-0-value-date').val('');
        $('#edit-field-event-register-period-0-end-value-date').val('');
        $('#edit-field-event-register-period-0-value-time').val('');
        $('#edit-field-event-register-period-0-end-value-time').val('');
      }
    });

  },
};

})(jQuery, Drupal);
