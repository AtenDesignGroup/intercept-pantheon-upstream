/**
 * @file
 * Provides JavaScript for recurring event node forms.
 */

(function ($, Drupal) {

/**
 * Provides a slimmer widget for recurring events.
 */
Drupal.behaviors.eventRecurring = {
  options: {},

  attach: function (context, settings) {
    var $root = this;
    $('.intercept-event-recurring-container', context).once().each(function(id, el) {
      $root.bind($(el).uniqueId(), context, settings);
    });
  },

  bind: function ($container, context, settings) {
    $root = this;
    // Bind the enable button to populate values.
    var nodeId = $container.data('event-id');
    var eventCount = function() {
      return settings.intercept.events[nodeId].recurringEventCount;
    }
    var hasEvents = function() {
      return settings.intercept.events[nodeId]
        && settings.intercept.events[nodeId].hasRecurringEvents;
    }
    $container.find('.intercept-event-recurring-enable').once().on('change', function(e) {
      if (!$(this).is(':checked') && hasEvents()) {
        if (alert(Drupal.t('Warning: Clicking disable will delete !count recurring events.', { '!count': eventCount() })) != true) {
          e.preventDefault();
          return;
        }
      }
    });
  },
};

})(jQuery, Drupal);
