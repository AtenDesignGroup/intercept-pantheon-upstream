/**
 * @file
 * Provides JavaScript for delayed keyup response form.
 * Keyword: delay_keyup
 */

(function ($, Drupal) {
  Drupal.behaviors.delayed_keyup = {
    attach: function (context, settings) {
      $('input.delayed-keyup', context).last().triggerHandler('delayed_keyup');
      var typingTimer;
      var delay = 1100;
      $('input.delayed-keyup', context).not('.picker__input').once('delayed_keyup').on('keyup', function (e) {
        const $self = $(this);
        clearTimeout(typingTimer);
        if ($(this).val()) {
          var trigid = $(this);
          typingTimer = setTimeout(function () {
            if ($self.hasClass('form-time')) {
              var startTime = $self;
              roundMinutes(startTime);
            }
            trigid.triggerHandler('delayed_keyup');
          }, delay);
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
    // console.log('startMinutes = ' + startMinutes + ', so roundedMinutes = ' + roundedMinutes);
    startTime.val(startHours + ':' + roundedMinutes);
  }
})(jQuery, Drupal);
