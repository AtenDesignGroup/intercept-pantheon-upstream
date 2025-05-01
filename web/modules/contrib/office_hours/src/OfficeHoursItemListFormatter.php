<?php

namespace Drupal\office_hours;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\PluginSettingsBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\office_hours\Event\OfficeHoursEventDispatcher;
use Drupal\office_hours\Event\OfficeHoursEvents;
use Drupal\office_hours\OfficeHoursItemListSorter;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList;

/**
 * Factors out OfficeHoursItemList->getRows().
 *
 * Note: This is used in 3rd party code since #3219203.
 */
class OfficeHoursItemListFormatter {

  use StringTranslationTrait;

  /**
   * The DateHelper.
   *
   * @var \Drupal\office_hours\OfficeHoursDateHelper
   */
  protected $dateHelper;

  /**
   * The event dispatcher service.
   *
   * @var \Drupal\office_hours\Event\OfficeHoursEventDispatcher
   */
  private $eventDispatcher;

  /**
   * The ItemList.
   *
   * @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList
   */
  public $parent;

  /**
   * An object to maintain the sorted ItemList.
   *
   * @var \Drupal\office_hours\OfficeHoursItemListSorter;
   */
  public $sortedList;

  /**
   * Constructs a new formatter.
   *
   * @param \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList $parent
   *   The itemlist to be formatted.
   */
  public function __construct(OfficeHoursItemList $parent) {
    $this->dateHelper = new OfficeHoursDateHelper();
    $this->eventDispatcher = new OfficeHoursEventDispatcher();
    $this->parent = $parent;
    $this->sortedList = new OfficeHoursItemListSorter($parent);
  }

  /**
   * Returns the items of a field.
   *
   * Note: This is used in 3rd party code since #3219203.
   *
   * @param array $settings
   *   The settings.
   * @param array $field_settings
   *   The field settings.
   * @param array $third_party_settings
   *   The third party settings.
   * @param int $time
   *   A UNIX timestamp. If 0, set to 'REQUEST_TIME', alter-hook for Timezone.
   * @param \Drupal\Core\Field\PluginSettingsBase $plugin
   *   The widget/formatter at hand.
   *
   * @return array
   *   The formatted list of slots.
   */
  public function getRows(array $settings, array $field_settings, array $third_party_settings = [], int $time = 0, ?PluginSettingsBase $plugin = NULL) {
    $office_hours = [];

    // Clone the Item list, since the following code will change the items,
    // while custom installations need complete $items in theme preprocessing.
    // Note: do NOT move this into constructor.
    // @see https://www.drupal.org/project/office_hours/issues/3514516
    $items = clone $this->parent;
    // Sort the database values by day number, leaving slot order intact.
    // (Exceptions may be maintained in a random order.)
    // @todo In Formatter: itemList::getRows() or Widget: itemList::setValue().
    $items = $items->sort();
    $time = $this->dateHelper->getRequestTime($time, $items);

    // Let other modules alter the $items or $office_hours.
    $this->eventDispatcher->dispatchEvent(OfficeHoursEvents::PRE_FORMAT, $items, $office_hours, $time, $plugin);

    // Initialize $office_hours.
    // Create 7 empty weekdays, using date_api as key (0=Sun, 6=Sat).
    $weekdays = $this->dateHelper->weekDays(TRUE);
    // Reorder weekdays to match the first day of the week.
    $weekdays = $this->dateHelper->weekDaysOrdered($weekdays, $settings['office_hours_first_day']);

    // Prepare array of empty weekdays for normal days and seasons.
    $replace_exceptions = $settings['exceptions']['replace_exceptions'];
    // Remove seasons out of scope (in the past or far future).
    $horizon = $settings['exceptions']['restrict_seasons_to_num_days'];
    $seasons = $items->getSeasons(TRUE, FALSE, 'ascending', $time, $horizon);
    foreach ($seasons as $season_id => $season) {

      // First, add season header. It must be before the season days.
      // But do not add for regular 'season'.
      // Directly replace with exception day, if needed.
      if ($season_id) {
        // Add caption for plain text formatter.
        $day = $season_id + OfficeHoursDateHelper::SEASON_DAY_MIN;
        $caption = $season->getName();
        $this->addRowsCaption($office_hours, $day, $time, $caption);
      }
      // Then, add 7 season days, since each season has a week formatter.
      foreach ($weekdays as $day => $label) {
        $day = $season_id + $day;
        $this->addOfficeHours($office_hours, $day, $time, $replace_exceptions);
      }
    }

    // Remove excessive exception days.
    $horizon = $settings['exceptions']['restrict_exceptions_to_num_days'];
    $this->keepExceptionDaysInHorizon($items, $horizon);

    // Add Exceptions caption. Exception slots are added after this caption.
    if (!$replace_exceptions && $items->countExceptionDays() > 0) {
      // Add caption for plain text formatter. @todo Move to function.
      if ($caption = $settings['exceptions']['title']) {
        $day = OfficeHoursDateHelper::EXCEPTION_DAY_MIN;
        $this->addRowsCaption($office_hours, $day, $time, $caption);
      }
    }

    // Move items to $office_hours.
    $iterator = $items->getIterator();
    for ($iterator->rewind(); $iterator->valid(); $iterator->next()) {
      /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
      $item = $iterator->current();

      // Filter on the valid seasons (past seasons have been removed).
      // Note: weekdays and exception dates have valid SeasonId = 0.
      if (!isset($seasons[$item->getSeasonId()])) {
        continue;
      }

      // Add time slot to $office_hours.
      // Week, Season slots are completing an existing array element,
      // Exception slots are adding an element to the array.
      $this->addOfficeHours($office_hours, $item, $time, $replace_exceptions);
    }

    // Mark the current time slot.
    $current_item = $this->getCurrentSlot($time);
    if ($current_item) {
      $office_hours[$current_item->day]['is_current_slot'] = TRUE;
    }

    // Mark the next time slot.
    $next_day = $this->getNextDay($time);
    if ($next_day) {
      $day_number = $next_day[0]->day;
      if ($office_hours[$day_number] ?? FALSE) {
        $office_hours[$day_number]['is_next_day'] = TRUE;
      }
    }

    /*
     * We have a list of all possible rows, marking the next and current day.
     * Now, filter according to formatter settings.
     */

    // Compress all slots of the same day into one item.
    if ($settings['compress']) {
      $office_hours = $this->compressSlots($office_hours);
    }

    // Group identical, consecutive days into one item.
    if ($settings['grouped']) {
      $office_hours = $this->groupDays($office_hours, $settings, $field_settings);
    }

    // Return the filtered days/slots/items/rows.
    switch ($settings['show_closed']) {
      case 'all':
        // Nothing to do. All closed days are already added above.
        break;

      case 'open':
        $office_hours = $this->keepOpenDays($office_hours);
        break;

      case 'next':
        $office_hours = $this->keepNextDay($office_hours);
        break;

      case 'none':
        $office_hours = [];
        break;

      case 'current':
        $office_hours = $this->keepCurrentDay($office_hours);
        break;
    }

    // Format the label, start and end time into one slot.
    $office_hours = $this->formatTimeSlots($office_hours, $settings, $field_settings);

    // Let other modules alter the $items or $office_hours.
    $event = $this->eventDispatcher->dispatchEvent(OfficeHoursEvents::POST_FORMAT, $items, $office_hours, $time, $plugin);

    return $event->officeHours;
  }

  protected function addRowsCaption(array &$office_hours, $day, $time, $caption) {
    $this->addOfficeHours($office_hours, $day, $time, FALSE);
    // Add caption for plain text formatter. @todo Move to function.
    end($office_hours);
    $lastElement = &$office_hours[key($office_hours)];
    $lastElement['caption'] = $caption;
  }

  /**
   * Add an item to the array.
   *
   * @param array $office_hours
   *   Office hours array.
   * @param int|\Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item
   *   The time slot to be added.
   * @param int $time
   *   The actual UNIX date/timestamp to use.
   * @param bool $replace_exceptions
   *   Indicator to yes/no replace the weekdays with exception date values.
   *   (Currently using a 'rolling' calendar, not a 'current week' calendar.)
   */
  protected function addOfficeHours(&$office_hours, $item, int $time, bool $replace_exceptions) {
    $day = is_int($item) ? $item : $item->day;
    // Add initial empty day data.
    $office_hours[$day] ??= ['day' => $day] + $this->getOfficeHoursDefault();

    if (is_object($item)) {
      if ((!$replace_exceptions) || ($day > OfficeHoursDateHelper::SEASON_DAY_MIN)) {
        // Add day data per time slot.
        // Avoid duplicates for weekdays, if replace_exceptions is set.
        $office_hours[$day]['items'][] = $item;
      }
    }
    elseif (is_int($item)) {
      if ($replace_exceptions && $day < OfficeHoursDateHelper::SEASON_DAY_MIN) {
        // During initial weekday setup, replace with exception data.
        // Needed, because in $items, empty days are not visited again.
        // Check for $replace_exceptions, to avoid duplicate records.
        $sorted_list = $this->sortedList->getSortedItemList($time);

        // Get today's weekday.
        $weekday = $this->dateHelper->getWeekday($time);
        // Determine if the $day is this or next week.
        $week = ($day >= $weekday) ? 'this' : 'next';
        // Get date of $day for the exception day lookup.
        $slot_date = strtotime($day - 1 . " day $week week midnight");
        // Finally, replace weekdays with exception date slots.
        $office_hours[$day]['items'] = $sorted_list[$slot_date] ?? [];
      }
    }
  }

  /**
   * Returns the default values for all parameters.
   *
   * @return array
   *   Array with default values for theming.
   */
  protected function getOfficeHoursDefault() {
    // Prepare a complete structure for theming.
    $default_office_hours = [
      // Key data.
      'day' => NULL,
      'endday' => NULL,
      // Source data.
      'items' => [],
      // Derived day attributes.
      'is_current_slot' => FALSE,
      'is_next_day' => FALSE,
      // Formatted data.
      'label' => '',
      'formatted_slots' => [],
      'comments' => [],
    ];
    return $default_office_hours;
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
  protected static function compressSlots(array $office_hours) {
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */

    foreach ($office_hours as $key => &$info) {
      foreach ($info['items'] as $day_index => $item) {

        if (!$item->isCompressed ?? FALSE) {
          // Item is already compressed.
          // E.g., on schema formatter, already compressed in table formatter.
          return $office_hours;
        }

        if ($day_index == 0) {
          // Fetch the first slot of the day into an array element.
          $compressed_item = $item;
          $comments = [$compressed_item->comment];
        }
        else {
          // Compress other slots in first slot.
          $compressed_item->starthours = min($compressed_item->starthours, $item->starthours);
          $compressed_item->endhours = max($compressed_item->endhours, $item->endhours);
          // Add, copy comments into first slot.
          $comments[] = $item->comment;
          $compressed_item->set('comment', $comments);

          $compressed_item->isCompressed = TRUE;
          $item->isCompressed = TRUE;
          // Remove subsequent item.
          unset($info['items'][$day_index]);
        }
      }
    }

    return $office_hours;
  }

  /**
   * Formatter: group days with same slots into 1 line.
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
  protected function groupDays(array $office_hours, array $settings, array $field_settings) {
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $grouped_item */

    // Keys 0-7 are for sorted Weekdays.
    $previous_key = -100;
    $previous_day = [
      'day' => -100,
      'formatted_slots' => 'dummy',
      'comments' => 'dummy',
    ];
    // The timestamp difference between today and yesterday.
    $one_day = 86400;

    foreach ($office_hours as $key => &$info) {
      // Wrap and unwrap $info in array,
      // in order to get a local version of 'formatted_slots'.
      $this_day = [$info];
      $this_day = $this->formatTimeSlots($this_day, $settings, $field_settings);
      $this_day = reset($this_day);

      if ((string) $this_day['formatted_slots'] == (string) $previous_day['formatted_slots']
      && $this_day['comments'] == $previous_day['comments']) {
        $day = $info['day'];
        // Week/Season days are always consecutive, since days 1-7 are listed.
        if ($this->dateHelper->isExceptionDay($day)) {
          // Check consecutive days (previous endday is yesterday).
          // Or should exceptions never be grouped?
          if ((($day - $one_day) !== $previous_day['endday'])) {
            continue;
          }
        }

        $this_day['day'] = $previous_day['day'];
        $this_day['endday'] = $day;
        $this_day['is_current_slot'] |= $previous_day['is_current_slot'];
        $this_day['is_next_day'] |= $previous_day['is_next_day'];

        $info['day'] = $previous_day['day'];
        $info['endday'] = $day;
        $info['is_current_slot'] |= $previous_day['is_current_slot'];
        $info['is_next_day'] |= $previous_day['is_next_day'];

        unset($office_hours[(int) $previous_key]);
      }
      $previous_key = (int) $key;
      $previous_day = $this_day;
      // Fill 'endday' to have easier calculation for ExceptionDays.
      $previous_day['endday'] ??= $previous_day['day'];
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
      if ($this->dateHelper->isValidDate($key)) {
        // Exception dates + header are always displayed.
        $result[$key] = $info;
      }
      elseif (!empty($info['items'])) {
        // Open days are copied into result.
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
   *   Filtered office hours array.
   */
  protected function keepNextDay(array $office_hours) {
    $result = [];
    foreach ($office_hours as $key => $info) {
      if ($info['is_current_slot'] || $info['is_next_day']) {
        $result[$key] = $info;
        return $result;
      }
    }
    return $result;
  }

  /**
   * Formatter: remove all days, except for today.
   *
   * @param array $office_hours
   *   Office hours array.
   *
   * @return array
   *   Filtered office hours array.
   */
  protected function keepCurrentDay(array $office_hours) {
    $result = [];

    $today = $this->dateHelper->today();
    $weekday = $this->dateHelper->getWeekday($today);

    $sorted_days = $this->sortedList->getSortedItemList($today);

    $current_day = $sorted_days[$today] ?? NULL;

    // Mark the current day, even if it is closed.
    $day_number = $current_day ? $current_day[0]->day : $weekday;
    $result[$day_number] = $office_hours[$day_number] ?? [];
    if (!$result) {
      foreach ($office_hours as $key => $info) {
        // Because of grouped days, no direct read possible.
        if (($info['day'] <= $day_number) && ($day_number <= $info['endday'])) {
          // @todo When grouped and first day of week is not Sunday.
          $result[$key] = $info;
        }
      }
    }
    return $result;
  }

  /**
   * Formatter: format the list of daily Office hours.
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
  protected function formatTimeSlots(array $office_hours, array $settings, array $field_settings) {
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */

    $slot_separator = $settings['separator']['more_hours'];

    foreach ($office_hours as $key => &$info) {
      // Process the time slots for this day.
      $all_day = NULL;

      // @todo No $item exists for Closed days.
      // @todo Grouped Exception days with 'current day' formatter
      $index = 0;
      $info['items'][$index] ??= $this->parent->createItem(0, []);

      foreach ($info['items'] as $day_delta => $item) {
        switch (TRUE) {
          case ($all_day |= $item->all_day) && $day_delta > 0:
            // Additional slots are forbidden for all_day open days.
            // @todo Disable 'more slots' for all_day in JS.
            // Clear item, for consistent comments, etc.
            $item->setValue([], FALSE);
            break;

          case $item->isEmpty():
            // Do nothing.
            break;

          default:
            // Collect the formatted time slot in the day.
            $formatted_slot = $item->formatTimeSlot($settings);
            if (!empty($formatted_slot)) {
              $info['formatted_slots'][] = $formatted_slot;
            }
            break;
        }
      }

      // @todo 'endday' is only set on last item of the day. Not on first. Why?
      // @todo The following should not change the $item.
      // That is wrong. This is now solved by cloning the $items.
      $item->set('day', $info['day'] ?? NULL, FALSE);
      $item->set('endday', $info['endday'] ?? NULL, FALSE);
      // Format the label (weekday, exception day).
      $info['label'] = $item->label($settings);
      // Format comments (Note: before 'formatted_slots').
      $info['comments'] = $this->formatComments($info, $settings, $field_settings);

      // Convert the formatted slots array into string. Include special items.
      switch (TRUE) {
        case empty($info['formatted_slots']) && empty($info['comments']):
          $info['formatted_slots'] = $settings['closed_format']
            ? $this->t(Html::escape($settings['closed_format']),
              [],
              ['context' => 'office_hours'])
            : '';
          break;

        // case $item->isExceptionDay():
        // case $item->isSeasonHeader():
        // case $item->isSeasonDay():
        // case $item->isWeekDay():
        default:
          $all_day_comment = $item->isExceptionDay()
            ? $settings['exceptions']['all_day_format']
            : $settings['all_day_format'];
          $info['formatted_slots'] = $all_day
            ? $this->t(Html::escape($all_day_comment))
            : implode($slot_separator, $info['formatted_slots']);
          break;
      }

    }
    return $office_hours;
  }

  /**
   * Process comments in special cases.
   *
   * @param array $info
   *   The day info.
   * @param array $settings
   *   The formatter settings.
   * @param array $field_settings
   *   The field settings.
   *
   * @return array
   *   The formatted comments, depending on translation option.
   */
  private function formatComments(array $info, array $settings, array $field_settings) {
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */

    // Comments may not be set to an array, yet.
    $info['comments'] ??= [];

    // Remove the Season name from comments.
    if (OfficeHoursDateHelper::isSeasonHeader($info['day'])) {
      $info['comments'] = [];
      return $info['comments'];
    }

    foreach ($info['items'] as $day_delta => $item) {
      $comment = $item->comment;
      if ($comment) {
        // Collect the comment of the time slot in the day.
        // Note: compressed days are already an array.
        $comment = is_array($comment) ? $comment : [$comment];
        $info['comments'] = array_merge($info['comments'], $comment);
      }
    }

    // Format and translate comments.
    switch ($field_settings['comment']) {
      case 2:
        // Translatable comments in plain text, no HTML.
        $info['comments'] = array_map('Drupal\Component\Utility\Html::escape', $info['comments']);
        $info['comments'] = array_map('t', $info['comments']);
        break;

      case 1:
        // Allow comments with HTML, without translations.
        // @todo Support translations.
        $info['comments'] = array_map('Drupal\Component\Utility\Html::normalize', $info['comments']);
        break;

      default:
        // Comments are not allowed, but may have been entered somehow.
        $info['comments'] = [];
        break;
    }

    // Concatenate the comment lines.
    $slot_separator = $settings['separator']['more_hours'];
    $info['comments'] = implode($slot_separator, $info['comments']);
    // For compressed items, remove trailing separator.
    $info['comments'] = rtrim($info['comments'], $slot_separator);

    return $info['comments'];
  }

  /**
   * Get the current slot and the next day from the Office hours.
   *
   * - Attribute 'current' is set on the active slot.
   * - Variable $this->currentSlot is set to slot data.
   * - Variable $this->currentSlot is returned.
   *
   * @param int $time
   *   A UNIX timestamp. If 0, set to 'REQUEST_TIME', alter-hook for Timezone.
   *
   * @return \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem|null
   *   The current slot data, if any.
   */
  public function getCurrentSlot(int $time = 0) {
    $next_day = $this->sortedList->getNextDay($time);

    foreach ($next_day as $date => $day) {
      foreach ($day as $day_index => $item) {
        if ($item->isOpen($time)) {
          return $item;
        }
      }
    }
    return NULL;
  }

  /**
   * Returns the slots of the current/next open day.
   *
   * @param int $time
   *   A UNIX timestamp. If 0, set to 'REQUEST_TIME', alter-hook for Timezone.
   *
   * @return \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem[]|null
   *   A list of time slots.
   */
  public function getNextDay(int $time = 0) {
    $next_day = $this->sortedList->getNextDay($time);

    // Remove the date key before returning the time slots of the day.
    return current($next_day);
  }

  /**
   * Formatter: remove all Exception days behind horizon.
   *
   * @param $items
   *   The ItemList.
   * @param int $horizon
   *   The number of days in the future.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   Filtered Item list.
   */
  public function keepExceptionDaysInHorizon($items, $horizon) {
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
    $items->filter(function (OfficeHoursItem $item) use ($horizon) {
      if (!$item->isExceptionDay()) {
        return TRUE;
      }
      if ($horizon == 0) {
        // Exceptions settings are not set / submodule is disabled.
        return FALSE;
      }
      /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursExceptionsItem $item */
      if ($item->isInRange(0, $horizon)) {
        return TRUE;
      }
      return FALSE;
    });

    return $items;
  }

}
