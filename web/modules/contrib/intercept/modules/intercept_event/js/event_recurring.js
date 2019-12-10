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
      $root.handle($(el).uniqueId(), context, settings);
    });
    $('.form-item-recurring-event-readable-value, #edit-recurring-event-date', context).once().hide();
  },

  initialize: function($container, context, settings) {
    var $eventStartDate = $($container.data('start-date-selector')).val();
    $container.find('.form-item-recurring-event-date-start .picker__input', context).val($eventStartDate);
    // Fill in the hidden input for recurring start date so that it works even
    // ...if they don't interact with the datepicker.
    $container.find('input[name="recurring_event[date][start]"]', context).val($eventStartDate);
    // Hide a couple of inputs we don't really need to see.
    $container.find('.form-item-recurring-event-readable-value, #edit-recurring-event-date', context).hide();
  },

  populate: function($container, context, settings) {
    var elid = $container.attr('id');
    $root.options[elid] = {};

    var value = $container.find('.intercept-event-recurring-raw', context).val();
    var rrule = RRule.fromString(value);
    var options = rrule.options;
    $container.find('.intercept-event-recurring-value', context).each(function(vid, vel) {
      var $value = $(vel);
      var $dataName = $value.data('intercept-event-recurring-name');
      if ($dataName == 'until') {
        return;
      }
      // Skip freq because of a bug in material ui.
      if (options[$dataName] && $dataName != 'freq') {
        $value.val(options[$dataName]);
      }
    });
  },

  collect: function ($container, context, settings, clicked) {
    var elid = $container.attr('id');
    $root.options[elid] = {};

    var clicked_name = $(clicked).data('intercept-event-recurring-name');
    $container.find('.intercept-event-recurring-value', context).each(function(vid, vel) {
      var $value = $(vel);
      var $dataName = $value.data('intercept-event-recurring-name');
      if ($dataName) {
        if (clicked_name == 'count' && $dataName == 'until') {
          if ($root.options[elid]['until']) { delete $root.options[elid]['until']; }
          $value.val('');
        }
        if (clicked_name == 'until' && $dataName == 'count') {
          if ($root.options[elid]['count']) { delete $root.options[elid]['count']; }
          $value.val('');
        }
        var value = $value.val();
        if (value) {
          if ($dataName == 'until') {
            value = new Date(Date.parse($value.val()));
          }
          $root.options[elid][$dataName] = value;
        }
      }
    });

    var options = this.options[$container.attr('id')];
    var rule = new RRule(options);
    $container.find('.intercept-event-recurring-raw').val(rule.toString());
    $container.find('.intercept-event-recurring-readable').val(rule.toText());
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
      if ($(this).is(':checked')) {
        $root.initialize($container, context);
      }
    });
    // Collect each value and update raw value on value updates.
    $container.find('.intercept-event-recurring-value', context).each(function(vid, vel) {
      var $value = $(vel);
      $value.once().on('change', function() {
        var $container = $(this).parents('.intercept-event-recurring-container');
        $root.collect($container, context, settings, this);
      });
    });
    // If it's checked, and we change the base event date value, let's update the first recurrence date.
    $('#edit-field-date-time-0-value-date').on('change', function(e) {
      if ($container.find('.intercept-event-recurring-enable').is(':checked')) {
        $root.initialize($container, context);
      }
    });
  },

  handle: function($container, context, settings) {
    var $root = this;
    $root.bind($container, context, settings);
    if ($container.find('.intercept-event-recurring-raw').val().length > 0) {
      $root.populate($container, context, settings);
    }
  },
};

})(jQuery, Drupal);
