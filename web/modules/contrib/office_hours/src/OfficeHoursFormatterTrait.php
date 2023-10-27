<?php

namespace Drupal\office_hours;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface;

/**
 * Factors out OfficeHoursItemList->getItems()->getRows().
 *
 * Note: This is used in 3rd party code since #3219203.
 */
trait OfficeHoursFormatterTrait {

  /**
   * An integer representing the next open day.
   *
   * @var int
   */
  public $nextDay = NULL;

  /**
   * An array of items, keyed by UNIX timestamp, representing the current slot, if any.
   *
   * @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemBase[]
   */
  protected $currentSlot = [];

  /**
   * The DateHelper.
   *
   * @var \Drupal\office_hours\OfficeHoursDateHelper
   */
  protected $dateHelper = NULL;

  /**
   * Returns the items of a field.
   *
   * Note: This is used in 3rd party code since #3219203.
   *
   * @param array $values
   *   Result from FieldItemListInterface $items->getValue().
   * @param array $settings
   *   The settings.
   * @param array $field_settings
   *   The field settings.
   * @param array $third_party_settings
   *   The third party settings.
   * @param int $time
   *   A UNIX time stamp. Defaults to 'REQUEST_TIME'.
   *
   * @return array
   *   The formatted list of slots.
   */
  public function getRows(array $values, array $settings, array $field_settings, array $third_party_settings = [], int $time = 0) {
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList $this */
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */

    // Get the current time. May be adapted for User Timezone.
    $time = $this->getRequestTime($time);

    // Initialize the 'next' and 'current' slots for later usage,
    // before cloning or removing excessive exception days.
    $current_item = $this->getCurrentSlot($time);
    // Clone the Item list, since the following code will change the items,
    // while custom installations need complete $items in theme preprocessing.
    $itemList = clone $this;

    // Initialize $office_hours. @todo Use getValuesWithClosedDays().
    $office_hours = [];
    // Prepare a complete structure for theming.
    $default_office_hours = [
      'day' => NULL,
      'endday' => NULL,
      'is_current' => FALSE,
      'is_next_day' => FALSE,
      'slots' => [],
      'formatted_slots' => [],
      'comments' => [],
    ];
    // Create 7 empty weekdays, using date_api as key (0=Sun, 6=Sat).
    $weekdays = $this->dateHelper->weekDays(TRUE);
    // Reorder weekdays to match the first day of the week.
    $weekdays = $this->dateHelper->weekDaysOrdered($weekdays, $settings['office_hours_first_day']);
    // Get a list of seasons from the items.
    $seasons = $itemList->getSeasons($add_weekdays_as_season = TRUE, $add_new_season = FALSE);

    foreach ($seasons as $id => $season) {
      // First, add season header. But not for regular 'season'.
      if ($season->id()) {
        $day = OfficeHoursItem::SEASON_DAY;
        $office_hours[$day + $id] = ['day' => $day + $id] + $default_office_hours;
      }
      // Then, add season days.
      foreach ($weekdays as $day => $label) {
        $office_hours[$day + $id] = ['day' => $day + $id] + $default_office_hours;
      }
    }

    // Remove excessive exception days.
    $horizon = $settings['exceptions']['restrict_exceptions_to_num_days'] ?? 0;
    $itemList->keepExceptionDaysInHorizon($horizon);
    // Add an exception header.
    if ($itemList->hasExceptionDays()) {
      $day = OfficeHoursItem::EXCEPTION_DAY;
      $office_hours[$day] = ['day' => $day] + $default_office_hours;
    }

    // Move items to $office_hours.
    $iterator = $itemList->getIterator();
    for ($iterator->rewind(); $iterator->valid(); $iterator->next()) {
      $item = $iterator->current();
      $value = $item->getValue();
      $day = $value['day'];
      $day_delta = $value['day_delta'];

      // This should already exist for weekdays, not for Exception days.
      $office_hours[$day] = $office_hours[$day]
        ?? ['day' => $day] + $default_office_hours;

      $office_hours[$day]['slots'][] = [
        'all_day' => $value['all_day'],
        'start' => $value['starthours'],
        'end' => $value['endhours'],
        'comment' => $value['comment'] ?? '',
      ];
      // Add item, in order to use code using OO Item, not $value.
      $office_hours[$day]['items'][] = $item;
    }

    // Mark the current time slot.
    if ($current_item) {
      $day = $current_item->getValue()['day'];
      $office_hours[$day]['is_current'] = TRUE;
    }
    $next_day = $itemList->getNextDay($time);
    if ($next_day !== NULL) {
      $office_hours[$next_day]['is_next_day'] = TRUE;
    }

    /*
     * We have a list of all possible rows, marking the next and current day.
     * Now, filter according to formatter settings.
     */

    // Compress all slots of the same day into one item.
    if ($settings['compress']) {
      $office_hours = $itemList->compressSlots($office_hours);
    }
    // Group identical, consecutive days into one item.
    if ($settings['grouped']) {
      $office_hours = $itemList->groupDays($office_hours);
    }

    // From here, no more adding/removing, only formatting.
    // Format the label, start and end time into one slot.
    $office_hours = $itemList->formatSlots($office_hours, $settings, $field_settings);

    // Return the filtered days/slots/items/rows.
    switch ($settings['show_closed']) {
      case 'all':
        // Nothing to do. All closed days are already added above.
        break;

      case 'open':
        $office_hours = $itemList->keepOpenDays($office_hours);
        break;

      case 'next':
        $office_hours = $itemList->keepNextDay($office_hours);
        break;

      case 'none':
        $office_hours = [];
        break;

      case 'current':
        $office_hours = $itemList->keepCurrentDay($office_hours, $time);
        break;
    }
    return $office_hours;
  }

  /**
   * Formatter: compress the slots: E.g., 0900-1100 + 1300-1700 = 0900-1700.
   *
   * @param array $office_hours
   *   Office hours array.
   *
   * @return array
   *   Reformatted office hours array.
   */
  protected function compressSlots(array $office_hours) {

    foreach ($office_hours as $key => &$info) {
      if (is_array($info['slots']) && !empty($info['slots'])) {
        // Initialize first slot of the day.
        $compressed_slot = $info['slots'][0];
        // Compress other slot in first slot.
        foreach ($info['slots'] as $slot) {
          $compressed_slot['start'] = min($compressed_slot['start'], $slot['start']);
          $compressed_slot['end'] = max($compressed_slot['end'], $slot['end']);
          // @todo Compress comment.
        }
        $info['slots'] = [0 => $compressed_slot];
      }
    }
    return $office_hours;
  }

  /**
   * Formatter: group days with same slots into 1 line.
   *
   * @param array $office_hours
   *   Office hours array.
   *
   * @return array
   *   Reformatted office hours array.
   */
  protected function groupDays(array $office_hours) {
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $grouped_item */

    // Keys 0-7 are for sorted Weekdays.
    $previous_key = -100;
    $previous_day = ['slots' => 'dummy'];

    foreach ($office_hours as $key => &$info) {
      // @todo Enable groupDays() for Exception days.
       if (!OfficeHoursDateHelper::isExceptionDay($info['day'])) {

        if ($info['slots'] == $previous_day['slots']) {
          $info['endday'] = $info['day'];
          $info['day'] = $previous_day['day'];
          $info['is_current'] = $info['is_current'] || $previous_day['is_current'];
          $info['is_next_day'] = $info['is_next_day'] || $previous_day['is_next_day'];
          unset($office_hours[(int) $previous_key]);
        }
      }
      $previous_key = (int) $key;
      $previous_day = $info;
    }

    return $office_hours;
  }

  /**
   * Formatter: remove closed days, keeping open days.
   *
   * @param array $office_hours
   *   Office hours array.
   *
   * @return array
   *   Reformatted office hours array.
   */
  protected function keepOpenDays(array $office_hours) {
    $result = [];
    foreach ($office_hours as $key => $info) {
      if ($key >= OfficeHoursItem::EXCEPTION_DAY) {
        // Exceptions are always displayed.
        $result[$key] = $info;
      }
      elseif (!empty($info['slots'])) {
        $result[$key] = $info;
      }
    }
    return $result;
  }

  /**
   * Formatter: remove all days, except the first open day.
   *
   * @param array $office_hours
   *   Office hours array.
   *
   * @return array
   *   Reformatted office hours array.
   */
  protected function keepNextDay(array $office_hours) {
    $result = [];
    foreach ($office_hours as $key => $info) {
      if ($info['is_current'] || $info['is_next_day']) {
        $result[$key] = $info;
      }
    }
    return $result;
  }

  /**
   * Formatter: remove all days, except for today.
   *
   * @param array $office_hours
   *   Office hours array.
   * @param int $time
   *   A UNIX time stamp.
   *
   * @return array
   *   Reformatted office hours array.
   */
  protected function keepCurrentDay(array $office_hours, int $time) {
    $result = [];

    $weekday = $this->dateHelper->getWeekday($time);

    // Loop through all items.
    // Keep the current one.
    foreach ($office_hours as $info) {
      if ($info['day'] == $weekday) {
        $result[$weekday] = $info;
      }
    }
    return $result;
  }

  /**
   * Formatter: format the office hours list.
   *
   * @param array $office_hours
   *   Office hours array.
   * @param array $settings
   *   User settings array.
   * @param array $field_settings
   *   User field settings array.
   *
   * @return array
   *   Reformatted office hours array.
   */
  protected function formatSlots(array $office_hours, array $settings, array $field_settings) {
    $slot_separator = $settings['separator']['more_hours'];

    foreach ($office_hours as $key => &$info) {
      $info['label'] = '';
      $info['formatted_slots'] = [];
      $info['comments'] = [];

      /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList $this */
      /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
      // Create Item @todo Make sure this extra item does not corrupt stuff.
      /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $render_item */
      $render_item = $this->createItem(0, $info);

      $index = 0;
      $item = $info['items'][$index] ?? NULL;
      // No $item exists for: Closed day. Or Exception header.
      if ($item && (get_class($item) !== get_class($render_item))) {
        $render_item = clone $item;
      }

      // Format the label (weekday, exception day).
      $render_item->set('day', $info['day'] ?? NULL, FALSE);
      $render_item->set('endday', $info['endday'] ?? NULL, FALSE);
      $render_item->set('comment', $item->comment ?? NULL, FALSE);
      $info['label'] = $render_item->getLabel($settings);

      // Format the slots.
      $all_day = NULL;
      foreach ($info['slots'] as $day_delta => &$slot_data) {
        // Do not process additional slots in some cases.
        if (($all_day |= $slot_data['all_day']) && $day_delta > 0) {
          // Additional slots are forbidden for all_day.
          // @todo Disable 'more slots' for all_day in JS.
          continue;
        };

        $item = $info['items'][$day_delta];
        if (!$item->isEmpty()) {
          $render_item->set('starthours', $info['slots'][$day_delta]['start'] ?? NULL, FALSE);
          $render_item->set('endhours', $info['slots'][$day_delta]['end'] ?? NULL, FALSE);
          $formatted_slot = $render_item->formatTimeSlot($settings);
          $comment = $slot_data['comment'];
          if ($formatted_slot || $comment) {
            // Store the formatted slot in the slot itself.
            $slot_data['formatted_slot'] = $formatted_slot;
            // Store the arrays of formatted slots & comments in the day.
            $info['formatted_slots'][] = $formatted_slot;
            // Always add comment to keep #columns aligned with time slot.
            $info['comments'][] = $comment;
          }
        }
      }

      /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList $this */
      if ($render_item->day == OfficeHoursItem::EXCEPTION_DAY) {
        // Do not display timeslot for Exception Header.
        $info['formatted_slots'] = '';
      }
      elseif ($all_day) {
        $info['formatted_slots'] = OfficeHoursDateHelper::isExceptionDay($info['day'])
          ? $this->t(Html::escape($settings['exceptions']['all_day_format']))
          : $this->t(Html::escape($settings['all_day_format']));
      }
      elseif (empty($info['formatted_slots'])) {
        $info['formatted_slots'] = $this->t(Html::escape($settings['closed_format']));
      }
      else {
        $info['formatted_slots'] = implode($slot_separator, $info['formatted_slots']);
      }

      // Process comments.
      if ($render_item->isSeasonHeader()) {
        $info['comments'] = [];
      }
      // Remove empty comment lines, to avoid separators.
      foreach ($info['comments'] as $index => $comment_line) {
        if ($comment_line == '') {
          unset($info['comments'][$index]);
        }
      }
      // Format and Translate comments.
      if ($field_settings['comment'] == 2) {
        // Translatable comments in plain text, no HTML.
        $info['comments'] = array_map('Drupal\Component\Utility\Html::escape', $info['comments']);
        $info['comments'] = array_map('t', $info['comments']);
      }
      elseif ($field_settings['comment'] == 1) {
        // Allow comments with HTML, without translations.
        // @todo Support translations.
        $info['comments'] = array_map('Drupal\Component\Utility\Html::normalize', $info['comments']);
      }
      else {
        // Comments are not allowed, but may have been entered somehow.
        $info['comments'] = [];
      }
      // Concatenate the comment lines.
      $info['comments'] = implode($slot_separator, $info['comments']);

    }
    return $office_hours;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentSlot(int $time = 0) {
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList $this */

    // Get the current time. May be adapted for User Timezone.
    $time = $this->getRequestTime($time);

    if ($this->currentSlot[$time] ?? FALSE) {
      return $this->currentSlot[$time];
    }

    $this->currentSlot[$time] = FALSE;
    $yesterday = strtotime('yesterday midnight');
    $today = strtotime('today midnight');

    $iterator = $this->getIterator();
    for ($iterator->rewind(); $iterator->valid(); $iterator->next()) {
      $item = $iterator->current();

      if ($item->isExceptionDay() || $item->isSeasonDay()) {
        if (!$item->isInRange($today, $today)) {
          // Do not use this day. We always have a weekday fallback.
          continue;
        }
      }

      if ($item->isOpen($time)) {
        $this->currentSlot[$time] = $item;
        // Do not stop the loop here, since $current_slot
        // (weekday) can be overridden by following season and exception.
      }
    }

    return $this->currentSlot[$time];
  }

  /**
   * {@inheritdoc}
   */
  public function getNextDay(int $time = 0)
  {
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList $this */

    // Get the current time. May be adapted for User Timezone.
    $time = $this->getRequestTime($time);

    if ($this->nextDay[$time] ?? FALSE) {
      return $this->nextDay[$time];
    }

    $weekday = $this->dateHelper->getWeekday($time);
    // 'Hi' format, with leading zero (0900).
    $now = $this->dateHelper->format($time, 'Hi');

    $next_day = NULL;

    $iterator = $this->getIterator();
    for ($iterator->rewind(); $iterator->valid(); $iterator->next()) {
      $item = $iterator->current();

      $slot = $item->getValue();
      $day = (int) $slot['day'];
      $start = (int) $slot['starthours'];
      $end = (int) $slot['endhours'];

      // Initialize to first day of (next) week, in case we're closed
      // the rest of the week. This works for any first_day.
      if ($day <= $weekday) {
        if (!isset($first_day_next_week) || ($day < $first_day_next_week)) {
          $first_day_next_week = $day;
        }
      }

      $day = $item->getWeekday();
      if ($day == $weekday) {
        if (($slot['starthours'] === NULL) && ($slot['endhours'] === NULL)) {
          // We are closed all day.
          // (Do not use $start and $end, which are integers.)
        }
        elseif (($start < $end) && ($end < $now)) {
          // We were open today, but are already closed.
        }
        elseif ($start > $now) {
          // We will open later today.
          $next_day = $day;
        }
        else {
          $next_day = $day;
        }
      }
      elseif ($day > $weekday) {
        if ($item->isExceptionDay()) {
          // @todo #3307517 Add 'next' support for Exception day.
          // @todo #3307517 Add 'next' support for Seasonal day.
        }
        elseif ($next_day === NULL) {
          $next_day = $day;
        }
        elseif ($next_day < $weekday) {
          $next_day = $day;
        }
        else {
          // Just for analysis.
        }
      }
      else {
        // Just for analysis.
      }

    }

    if (!isset($next_day) && isset($first_day_next_week)) {
      $next_day = $first_day_next_week;
    }

    $this->nextDay[$time] = $next_day;
    return $this->nextDay[$time];
  }

  /**
   * Returns the timestamp for the current request.
   *
   * @param int $time
   *   The actual UNIX date/timestamp to use.
   *
   * @return int
   *   A Unix timestamp.
   *
   * @see \Drupal\Component\Datetime\TimeInterface
   */
  public function getRequestTime(int $time = 0) {
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList $this */

    if (!$time) {
      $time = \Drupal::time()->getRequestTime();
      // Call hook. Allows to alter the current time using a timezone.
      $entity = $this->getEntity();
      \Drupal::moduleHandler()->alter('office_hours_current_time', $time, $entity);
    }

    return $time;
  }

  /**
   * Create an array of exception days keyed by date.
   *
   * @return \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursExceptionsItem[]
   *   A keyed array of exception days keyed by date.
   */
  public function getExceptionDays() {
    $exception_days = [];

    foreach ($this->list as $item) {
      /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
      if ($item->isExceptionDay()) {
        $day = $item->getValue()['day'];
        $exception_days[$day][] = $item;
      }
    }

    return $exception_days;
  }

  /**
   * {@inheritdoc}
   */
  public function hasExceptionDays() {
    $exception_exists = FALSE;

    // Check if an exception day exists in the table.
    foreach ($this->list as $item) {
      $is_exception_day = $item->isExceptionDay();
      $exception_exists |= $is_exception_day;
    }

    return $exception_exists;
  }

  /**
   * {@inheritdoc}
   */
  public function isOpen(int $time = 0) {
    $current_item = $this->getCurrentSlot($time);
    return (bool) $current_item;
  }

  /**
   * A helper variable for keepExceptionDaysInHorizon.
   *
   * @var int
   */
  protected static $horizon;

  /**
   * Formatter: remove all Exception days behind horizon.
   *
   * @param int $horizon
   *   The number of days in the future.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   Filtered Item list.
   */
  public function keepExceptionDaysInHorizon($horizon) {
    self::$horizon = $horizon;

    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList $this */
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */

    $this->filter(function (OfficeHoursItem $item) {
      if (!$item->isExceptionDay()) {
        return TRUE;
      }
      if (self::$horizon == 0) {
        // Exceptions settings are not set / submodule is disabled.
        return FALSE;
      }
      /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursExceptionsItem $item */
      if ($item->isInRange(0, self::$horizon)) {
        return TRUE;
      }
      return FALSE;
    });
    return $this;
  }

}
