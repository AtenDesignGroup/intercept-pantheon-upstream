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

    // When changing the start date, make the end date match.
    $('#edit-field-date-time-0-value-date', context).once().change(function() {
      var endDate = $('#edit-field-date-time-0-end-value-date');
      if ($(this).val() > endDate.val()) {
        endDate.val($(this).val());
        $('[name="field_date_time[0][end_value][date]"]').val($(this).val());
      }
    });
    // Bulk room reservation version
    $('#edit-field-date-time-0-start-date', context).once().change(function() {
      var endDate = $('#edit-field-date-time-0-end-date');
      if ($(this).val() > endDate.val()) {
        endDate.val($(this).val());
        $('[name="field_date_time[0][end][date]"]').val($(this).val());
      }
    });

    // Show/hide hosting location field.
    $('#edit-field-hosting-location-wrapper').hide();
    $('#edit-field-hosting-location').val('_none');
    $('#edit-field-hosting-location option:contains(Online)').each(function() {
      $(this).remove();
    });
    if ($('#edit-field-location option:contains(Online)').prop('selected') == true) {
      $('#edit-field-hosting-location-wrapper').show();
    }

    // Ensure 15 minute intervals on time fields.
    var wto;
    var fields = [
      '#edit-field-date-time-0-value-time',
      '#edit-field-date-time-0-end-value-time',
      '#edit-field-event-register-period-0-value-time',
      '#edit-field-event-register-period-0-end-value-time',
      // Bulk room reservation
      '#edit-field-date-time-0-start-time',
      '#edit-field-date-time-0-end-time',
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

    // Check title vs. alt text on image.
    var timer, delay = 500;
    $('#edit-title-0-value, input[id^="edit-image-primary-form-0-field-media-image-0-alt"]', context).once().bind('keydown blur change', function(e) {
      var _this = $(this);
      clearTimeout(timer);
      timer = setTimeout(function() {
        console.log(_this.val());
        var titleValue = $('#edit-title-0-value').val();
        var altValue = $('input[id^="edit-image-primary-form-0-field-media-image-0-alt"]').val();
        if (titleValue == altValue) {
          // Give it focus and mark it as an error.
          $('input[id^="edit-image-primary-form-0-field-media-image-0-alt"]').focus();
          $('.form-item--image-primary-form-0-field-media-image-0-alt label').addClass('has-error').text('Alternative text (Must NOT be the same as your event title)');
          $('.form-item--image-primary-form-0-field-media-image-0-alt input').addClass('error');
          // alert('Your image alt text cannot be the same as your event title.');

        }
        else {
          // Remove has-error class.
          $('.form-item--image-primary-form-0-field-media-image-0-alt label').removeClass('has-error').text('Alternative text');
          $('.form-item--image-primary-form-0-field-media-image-0-alt input').removeClass('error');
        }
      }, delay );
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
