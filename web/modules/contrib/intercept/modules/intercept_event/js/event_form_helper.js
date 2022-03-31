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

    $('#edit-field-date-time-0-value-date', context).once().change(function() {
      var endDate = $('#edit-field-date-time-0-end-value-date');
      if ($(this).val() > endDate.val()) {
        endDate.val($(this).val());
        $('[name="field_date_time[0][end_value][date]"]').val($(this).val());
      }
    });

    // Ensure 15 minute intervals on time fields.
    var wto;
    var fields = [
      '#edit-field-date-time-0-value-time',
      '#edit-field-date-time-0-end-value-time',
      '#edit-field-event-register-period-0-value-time',
      '#edit-field-event-register-period-0-end-value-time',
    ];
    $.each(fields, function(index, value) {
      $(value, context).once().change(function() {
        clearTimeout(wto);
        wto = setTimeout(function() { // Check after 1 second of idleness.
          var startTime = $(value);
          roundMinutes(startTime);
        }, 1000);
      });
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

  /**
   * Rounds minutes to 15 minute intervals in a given time.
   */
  function roundMinutes(startTime) {
    var startTimeVal = startTime.val();
    let [startHours, startMinutes] = startTimeVal.split(':');
    var roundedMinutes = (Math.round(startMinutes/15) * 15) % 60;
    if (roundedMinutes == 0 || roundedMinutes == 60) roundedMinutes = '00';
    // if (minutes == 60) { minutes = "00"; ++hours % 24; }
    startTime.val(startHours + ':' + roundedMinutes);
  }
})(jQuery, Drupal);
