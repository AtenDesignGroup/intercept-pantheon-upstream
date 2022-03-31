/**
 * @file
 * Provides JavaScript for delayed keyup response form.
 */

(function ($, Drupal) {
  Drupal.behaviors.delayed_keyup = {
    attach: function (context, settings) {
      $('input.delayed-keyup').not('.picker__input').once('delayed_keyup').each(function () {
        const $self = $(this);
        let timeout = null;
        const delay = $self.data('delay') || 700;
        const triggerEvent = $self.data('event') || 'delayed_keyup';

        $self.unbind('change blur').on('change blur', () => {
          clearTimeout(timeout);
          timeout = setTimeout(() => {
            // Ensure 15 minute intervals on time fields.
            // console.log('Trying the reservation form helper code.');
            if ($self.hasClass('form-time')) {
              var startTime = $self;
              roundMinutes(startTime);
            }
            // Check availability.
            $self.triggerHandler(triggerEvent);
          }, delay);
        });
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
