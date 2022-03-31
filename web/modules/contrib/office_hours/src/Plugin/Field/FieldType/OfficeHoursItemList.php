<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldItemList;
use Drupal\office_hours\Event\OfficeHoursEvents;
use Drupal\office_hours\Event\OfficeHoursUpdateEvent;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\OfficeHoursFormatterTrait;

/**
 * Represents an Office hours field.
 */
class OfficeHoursItemList extends FieldItemList implements OfficeHoursItemListInterface {

  use OfficeHoursFormatterTrait {
    getRows as getFieldRows;
  }

  /**
   * Helper for creating a list item object of several types.
   *
   * {@inheritdoc}
   */
  protected function createItem($offset = 0, $value = NULL) {

    if (!isset($value['day'])) {
      // Empty (added?) Item from List Widget.
      return parent::createItem($offset, $value);
    }

    // Normalize the data in the structure. @todo Needed? Also in getValue().
    $value = OfficeHoursItem::formatValue($value);

    // Use quasi Factory pattern to return Weekday or Exception item.
    if (!OfficeHoursDateHelper::isExceptionDay($value)) {
      // Add Weekday Item.
      return parent::createItem($offset, $value);
    }

    // Add Exception day Item.
    // @todo Move static variables to class level.
    static $pluginManager;
    static $exceptions_list = NULL;
    // First, create a special ItemList with Exception day field definition.
    if (!$exceptions_list) {
      $pluginManager = \Drupal::service('plugin.manager.field.field_type');
      // Get field definition of ExceptionsItem.
      $plugin_id = 'office_hours_exception';
      $field_definition = BaseFieldDefinition::create($plugin_id);
      // Create an ItemList with ExceptionsItems.
      $exceptions_list = new OfficeHoursItemList($field_definition);
    }
    // Then, add an item to the list with Exception day field definition.
    $item = $pluginManager->createFieldItem($exceptions_list, $offset, $value);

    // Pass item to parent, where it appears amongst Weekdays.
    return $item;
  }

  /**
   * {@inheritdoc}
   */
  public function getRows(array $settings, array $field_settings, array $third_party_settings, $time = NULL) {
    // @todo move more from getRows here, using itemList, not values.
    $this->keepExceptionDaysInHorizon($third_party_settings['office_hours_exceptions']['restrict_exceptions_to_num_days'] ?? NULL);
    return $this->getFieldRows($this->getValue(), $settings, $field_settings, $third_party_settings, $time);
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusTimeLeft(array $settings, array $field_settings) {

    // @see https://www.drupal.org/docs/8/api/cache-api/cache-max-age
    // If there are no open days, cache forever.
    if ($this->isEmpty()) {
      return Cache::PERMANENT;
    }

    $date = new DrupalDateTime('now');
    $today = $date->format('w');
    $now = $date->format('Hi');
    $seconds = $date->format('s');

    $next_time = '0000';
    $add_days = 0;
    // Get some settings from field. Do not overwrite defaults.
    // Return the filtered days/slots/items/rows.
    switch ($settings['show_closed']) {
      case 'all':
      case 'open':
      case 'none':
        // These caches never expire, since they are always correct.
        return Cache::PERMANENT;

      case 'current':
        // Cache expires at midnight.
        $next_time = '0000';
        $add_days = 1;
        break;

      case 'next':
        // Get the first (and only) day of the list.
        // Make sure we only receive 1 day, only to calculate the cache.
        $office_hours = $this->getRows($settings, $field_settings, []);
        $next = array_shift($office_hours);

        // Get the difference in hours/minutes between 'now' and next open/closing time.
        $first_time = NULL;
        foreach ($next['slots'] as $slot) {
          $start = $slot['start'];
          $end = $slot['end'];

          if ($next['startday'] != $today) {
            // We will open tomorrow or later.
            $next_time = $start;
            $add_days = ($next['startday'] - $today + OfficeHoursDateHelper::DAYS_PER_WEEK)
              % OfficeHoursDateHelper::DAYS_PER_WEEK;
            break;
          }
          elseif ($start > $now) {
            // We will open later today.
            $next_time = $start;
            $add_days = 0;
            break;
          }
          elseif (($start > $end) // We are open until after midnight.
            || ($start == $end) // We are open 24hrs per day.
            || (($start < $end) && ($end > $now)) // We are open, normal time slot.
          ) {
            $next_time = $end;
            $add_days = ($start < $end) ? 0 : 1; // Add 1 day if open until after midnight.
            break;
          }
          else {
            // We were open today. Take the first slot of the day.
            if (!isset($first_time_slot_found)) {
              $first_time_slot_found = TRUE;

              $next_time = $start;
              $add_days = OfficeHoursDateHelper::DAYS_PER_WEEK;
            }
            continue; // A new slot might come along.
          }
        }
        break;

      default:
        // We should have covered all options above.
        return Cache::PERMANENT;
    }

    // Set to 0 to avoid php error if time field is not set.
    $next_time = is_numeric($next_time) ? $next_time : '0000';
    // Calculate the remaining cache time.
    $time_left = $add_days * 24 * 3600;
    $time_left += ((int) substr($next_time, 0, 2) - (int) substr($now, 0, 2)) * 3600;
    $time_left += ((int) substr($next_time, 2, 2) - (int) substr($now, 2, 2)) * 60;
    $time_left -= $seconds; // Correct for the current minute.

    return $time_left;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Enable isOpen() for Exception days.
   */
  public function isOpen($time = NULL) {
    $office_hours = $this->keepCurrentSlot($this->getValue(), $time);
    return ($office_hours !== []);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    // Allow other modules to allow $values.
    if (FALSE) {
      // @todo Disabled until #3063782 is resolved.
      $this->dispatchUpdateEvent(OfficeHoursEvents::OFFICE_HOURS_UPDATE, $value);
    }
    parent::setValue($value, $notify);
  }

  /**
   * Dispatches an event.
   *
   * @param string $event_name
   *   The event to trigger.
   * @param array|null $value
   *   An array of values of the field items, or NULL to unset the field.
   *   Can be changed by EventSubscribers.
   *
   * @return \Drupal\sms\Event\SmsMessageEvent
   *   The dispatched event.
   */
  protected function dispatchUpdateEvent($event_name, &$value) {
    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher */
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event = new OfficeHoursUpdateEvent($value);
    $event = $event_dispatcher->dispatch($event);
    $value = $event->getValues();
    return $event;
  }

}
