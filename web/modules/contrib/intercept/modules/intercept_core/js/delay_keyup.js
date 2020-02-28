/**
 * @file
 * Provides JavaScript for delayed keyup response form.
 */

(function ($, Drupal) {
  Drupal.behaviors.delayed_keyup = {
    attach(context, settings) {
      $('input.delayed-keyup').not('.picker__input').each(function () {
        const $self = $(this);
        let timeout = null;
        const delay = $self.data('delay') || 700;
        const triggerEvent = $self.data('event') || 'delayed_keyup';

        $self.unbind('change blur').on('change blur', () => {
          clearTimeout(timeout);
          timeout = setTimeout(() => {
            $self.triggerHandler(triggerEvent);
          }, delay);
        });
      });
    },
  };
}(jQuery, Drupal));
