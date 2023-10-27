<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\office_hours\Element\OfficeHoursDatetime;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\OfficeHoursSeason;

/**
 * Plugin implementation of the 'office_hours' field type.
 *
 * @FieldType(
 *   id = "office_hours",
 *   label = @Translation("Office hours"),
 *   description = @Translation("This field stores weekly 'office hours' or 'opening hours' in the database."),
 *   default_widget = "office_hours_default",
 *   default_formatter = "office_hours",
 *   list_class = "\Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList",
 * )
 */
class OfficeHoursItem extends OfficeHoursItemBase {

  /**
   * The minimum day number for Seasonal days.
   *
   * Usage: $items->appendItem(['day' => OfficeHoursItem::EXCEPTION_DAY]).
   * Also used for SeasonHeader: $day = SeasonId + SEASON_DAY;
   *
   * @var int
   */
  const SEASON_DAY = 9;

  /**
   * The maximum day number for Seasonal weekdays.
   *
   * @var int
   */
  const SEASON_MAX_DAY_NUMBER = 1000000000;

  /**
   * The minimum day number for Exception days.
   *
   * @var int
   */
  const EXCEPTION_DAY = 1000000001;

  /**
   * Determines whether the item is a seasonal or regular Weekday.
   *
   * @return int
   *   0 if the Item is a regular Weekday,E.g., 1..9 -> 0.
   *   season_id if a seasonal weekday, E.g., 301..309 -> 300.
   */
  public function getSeasonId() {
    $day = $this->day;
    return OfficeHoursDateHelper::isSeasonDay($day)
      ? $day - $day % 100
      : 0;
  }

  /**
   * Determines whether the item is a seasonal or regular Weekday.
   *
   * @return int
   *   0 if the Item is a regular Weekday,E.g., 1..9 -> 0.
   *   season_id if a seasonal weekday, E.g., 301..309 -> 100..100.
     * @return bool
     *   True if the day_number is a seasonal weekday (100 to 100....7).
   */
  public function isSeasonDay() {
    return OfficeHoursDateHelper::isSeasonDay($this->day);
  }

  /**
   * Determines whether the item is a seasonal or regular Weekday.
   *
   * @return int
   *   0 if the Item is a regular Weekday,E.g., 1..9 -> 0.
   *   season_id if a seasonal weekday, E.g., 301..309 -> 100..100.
   */
  public function isSeasonHeader() {
    return OfficeHoursSeason::isSeasonHeader($this->day);
  }

  /**
   * Determines whether the item is a Weekday or an Exception day.
   *
   * @return bool
   *   TRUE if the item is Exception day, FALSE otherwise.
   */
  public function isExceptionDay() {
    return FALSE;
  }

  /**
   * Returns if a timestamp is in date range of x days to the future.
   *
   * @param int $from
   *   The days into the past/future we want to check the timestamp against.
   * @param int $to
   *   The days into the future we want to check the timestamp against.
   *
   * @return bool
   *   TRUE if the timestamp is in range.
   *   TRUE if $rangeInDays has a negative value.
   */
  public function isInRange($from, $to) {
    // Dummy function.
    return TRUE;
  }

  /**
   * Returns if a timestamp is in date range of x days to the future.
   *
   * @param int $time
   *   A timestamp. Might be adapted for User Timezone.
   *
   * @return bool
   *   TRUE if the $time is during timeslot.
   */
  public function isOpen($time)
  {
    $is_open = FALSE;

    $weekday = OfficeHoursDateHelper::getWeekday($time);
    // 'Hi' format, with leading zero (0900).
    $now = OfficeHoursDateHelper::format($time, 'Hi');

    $slot = $this->getValue();
    // Normalize to exception/season to weekday.
    $day = $this->getWeekday();
    $start = (int) $slot['starthours'];
    $end = (int) $slot['endhours'];

    // Check for Weekday and for Exception day ('midnight').
    if ($day == $weekday - 1 || ($day == $weekday + 6)) {
      // We were open yesterday evening, check if we are still open.
      if ($start >= $end && $end > $now) {
        $is_open = TRUE;
      }
    }
    elseif ($day == $weekday) {

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
        // We were open today, check if we are still open.
        if (
          ($start > $end) // We are open until after midnight.
          || ($end == 0) // We are open until midnight (24:00 or empty).
          || ($start == $end && !is_null($start)) // We are open 24hrs per day.
          || (($start < $end) && ($end > $now)) // We are open, normal time slot.
        ) {
          // We are open.
          $is_open = TRUE;
        }
      }
    }

    return $is_open;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->isValueEmpty($this->getValue());
  }

  /**
   * Determines whether the data structure is empty.
   *
   * @param array $value
   *   The value of a time slot: day, all_day, start, end, comment.
   *   A value 'day_delta' must be added in case of widgets and formatters.
   *   Example from HTML5 input, without comments enabled.
   *   @code
   *     array:3 [
   *       "day" => "3"
   *       "starthours" => array:1 [
   *         "time" => "19:30"
   *       ]
   *       "endhours" => array:1 [
   *         "time" => ""
   *       ]
   *     ]
   *   @endcode
   *
   * @return bool
   *   TRUE if the data structure is empty, FALSE otherwise.
   */
  public static function isValueEmpty(array $value) {
    // Note: in Week-widget, day is <> '', in List-widget, day can be '',
    // and in Exception day, day can be ''.
    // Note: test every change with Week/List widget and Select/HTML5 element!
    if (!isset($value['day']) && !isset($value['time'])) {
      return TRUE;
    }

    // If all_day is set, day is not empty.
    if ($value['all_day'] ?? FALSE) {
      return FALSE;
    }

    // Facilitate closed Exception days - first slots are never empty.
    if (OfficeHoursDateHelper::isExceptionDay($value['day'])) {
      switch ($value['day_delta'] ?? 0) {
        case 0:
          // First slot is never empty if an Exception day is set.
          // In this case, on that date, the entity is 'Closed'.
          // Note: day_delta is not set upon load, since not in database.
          // In ExceptionsSlot (Widget), 'day_delta' is added explicitly.
          // In Formatter ..?
          return FALSE;

        default:
          // Following slots. Continue with check for Weekdays.
      }
    }

    // Allow Empty time field with comment (#2070145).
    // For 'select list' and 'html5 datetime' hours element.
    if (isset($value['day'])) {
      if (OfficeHoursDatetime::isEmpty($value['starthours'] ?? '')
      && OfficeHoursDatetime::isEmpty($value['endhours'] ?? '')
      && empty($value['comment'] ?? '')
      ) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->format($value);
    parent::setValue($value, $notify);
  }

  /**
   * Normalizes the contents of the Item.
   *
   * @param array|null $value
   *   The value of a time slot; day, start, end, comment.
   *
   * @return array
   *   The normalised value of a time slot.
   */
  public static function format(&$value) {
    $day = $value['day'] ?? NULL;

    if ($day == OfficeHoursItem::EXCEPTION_DAY) {
      // An ExceptionItem is created with ['day' => EXCEPTION_DAY,].
      $day = NULL;
      $value = [];
    }

    // Set default values for new, empty widget.
    if ($day === NULL) {
      return $value += [
        'day' => '',
        'all_day' => FALSE,
        'starthours' => NULL,
        'endhours' => NULL,
        'comment' => '',
      ];
    }

    // Handle day formatting.
    if ($day && !is_numeric($day)) {
      // When Form is displayed the first time, $day is an integer.
      // When 'Add exception' is pressed, $day is a string "yyyy-mm-dd".
      $day = (int) strtotime($day);
    }
    elseif ($day !== '') {
      // Convert day number to integer to get '0' for Sunday, not 'false'.
      $day = (int) $day;
    }

    // Handle exception day 'more' slots.
    // The following should be in slot::valueCallback(),
    // But the results are not propagated into the widget.
    if ($value !== FALSE) {

      // This function is called in a loop over widget items.
      // Save the exception day for the first delta,
      // then use it in the following delta's of a day.
      static $day_delta = 0;
      static $previous_day = NULL;

      if ($previous_day === $day) {
        $day_delta++;
      }
      // Note: in ideal implementation, this is only needed in valueCallback(),
      // but values are not copied from slot to widget.
      // Only need to widget-specific massaging of form values,
      // All logical changes will be done in ItemList->setValue($values),
      // where the format() function will be called, also.
      // Process Exception days with 'more slots'.
      // This cannot be done in above form, since we parse $day over items.
      // Process 'day_delta' first, to avoid problem in isExceptionDay().
      elseif ($value['day'] === 'exception_day_delta') {
        $day = $previous_day;
        $day_delta++;
      }
      else {
        $previous_day = $day;
        $day_delta = 0;
      }
    }

    $starthours = $value['starthours'] ?? NULL;
    $endhours = $value['endhours'] ?? NULL;
    // Format to 'Hi' format, with leading zero (0900).
    // Note: the value may also contain a season date.
    if (!is_numeric($starthours)) {
      $starthours = OfficeHoursDateHelper::format($starthours, 'Hi');
    }
    if (!is_numeric($endhours)) {
      $endhours = OfficeHoursDateHelper::format($endhours, 'Hi');
    }
    // Cast the time to integer, to avoid core's error
    // "This value should be of the correct primitive type."
    // This is needed for e.g., '0000' and '0030'.
    $starthours = ($starthours === NULL) ? NULL : (int) $starthours;
    $endhours = ($endhours === NULL) ? NULL : (int) $endhours;

    // Handle the all_day checkbox.
    $all_day = (bool) ($value['all_day'] ?? FALSE);
    if ($all_day) {
      $starthours = $endhours = 0;
    }
    elseif ($starthours === 0 && $endhours === 0) {
      $all_day = TRUE;
      $starthours = $endhours = 0;
    }

    $value = [
      'day' => $day,
      'day_delta' => $day_delta,
      'all_day' => $all_day,
      'starthours' => $starthours,
      'endhours' => $endhours,
      'comment' => $value['comment'] ?? '',
    ];

    return $value;
  }

  /**
   * Formats a time slot, to be displayed in Formatter.
   *
   * @param array $settings
   *   The formatter settings.
   *
   * @return string
   *   Returns formatted time.
   */
  public function formatTimeSlot(array $settings) {
    $format = OfficeHoursDateHelper::getTimeFormat($settings['time_format']);
    $separator = $settings['separator']['hours_hours'];

    $start = OfficeHoursDateHelper::format($this->starthours, $format, FALSE);
    $end = OfficeHoursDateHelper::format($this->endhours, $format, TRUE);

    if (OfficeHoursDatetime::isEmpty($start)
      && OfficeHoursDatetime::isEmpty($end)) {
      // Empty time fields.
      return '';
    }

    $formatted_time = $start . $separator . $end;
    \Drupal::moduleHandler()->alter('office_hours_time_format', $formatted_time);

    return $formatted_time;
  }

  /**
   * Formats the labels of a Render element, like getLabel().
   *
   * @param array $settings
   *   The formatter settings.
   *
   * @return string
   *   The translated formatted day label.
   */
  public function getLabel(array $settings) {
    return OfficeHoursItem::formatLabel($settings, $this->getValue());
  }

  /**
   * Gets the weekday number.
   *
   * @return int
   *   Returns the weekday number(0=Sun, 6=Sat).
   */
  public function getWeekday()
  {
    return OfficeHoursDateHelper::getWeekday($this->day);
  }

  /**
   * Formats the labels of a Render element, like getLabel().
   *
   * @param array $settings
   *   The formatter settings.
   * @param array $value
   *   The Item values.
   *
   * @return string
   *   The translated formatted day label.
   */
  public static function formatLabel(array $settings, array $value) {
    $label = '';

    if ($value['day'] == OfficeHoursItem::EXCEPTION_DAY) {
      // @todo Remove code from file office_hours.theme.exceptions.inc .
      // @todo Move into OfficeHoursDateHelper::getLabel ??
      $label = $settings['exceptions']['title'] ?? '';
      return t($label);
    }

    $pattern = $settings['day_format'];
    // Return fast if weekday is not to be displayed.
    if ($pattern == 'none') {
      return $label;
    }

    // Get the label.
    $label = OfficeHoursDateHelper::getLabel($pattern, $value);
    $days_suffix = $settings['separator']['day_hours'];

    // Extend the label for Grouped days.
    if (isset($value['endday'])) {
      $day = $value['endday'];
      $label2 = OfficeHoursDateHelper::getLabel($pattern, ['day' => $day]);

      $group_separator = $settings['separator']['grouped_days'];
      $label .= $group_separator . $label2;
    }
    $label .= $days_suffix;

    return $label;
  }

}
