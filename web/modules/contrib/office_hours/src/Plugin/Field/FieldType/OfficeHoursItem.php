<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\office_hours\Element\OfficeHoursDatetime;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Plugin implementation of the 'office_hours' field type.
 *
 * @FieldType(
 *   id = "office_hours",
 *   label = @Translation("Office hours"),
 *   description = @Translation("Field to store weekly 'office hours' or 'opening hours', including seasons and exception days."),
 *   default_widget = "office_hours_exceptions",
 *   default_formatter = "office_hours_table",
 *   list_class = "\Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList",
 *   cardinality = -1,
 * )
 */
class OfficeHoursItem extends OfficeHoursItemBase {

  /**
   * Determines whether the item is a seasonal or regular Weekday.
   *
   * @return int
   *   0 if the Item is a regular Weekday,E.g., 1..9 -> 0.
   *   season_id if a seasonal weekday, E.g., 301..309 -> 300.
   */
  public function getSeasonId() {
    $day = $this->day;
    $season_id = OfficeHoursDateHelper::getSeasonId($day);
    return $season_id;
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
   * Determines whether the item is the Exceptions header.
   *
   * @return bool
   *   True if the day is the specially defined ExceptionsHeader.
   */
  public function isExceptionHeader() {
    return OfficeHoursDateHelper::isExceptionHeader($this->day);
  }

  /**
   * Determines whether the item is a seasonal Weekday.
   *
   * @return bool
   *   True if the day_number is a seasonal weekday (100 to 100....7).
   */
  public function isSeasonDay() {
    return OfficeHoursDateHelper::isSeasonDay($this->day);
  }

  /**
   * Determines whether the item is a season header.
   *
   * @return int
   *   0 if the Item is a regular Weekday,E.g., 1..9 -> 0.
   *   season_id if a seasonal weekday, E.g., 301..309 -> 100..100.
   */
  public function isSeasonHeader() {
    return OfficeHoursDateHelper::isSeasonHeader($this->day);
  }

  /**
   * Determines whether the item is a Weekday or an Exception day.
   *
   * @return bool
   *   TRUE if the item is Exception day, FALSE otherwise.
   */
  public function isWeekDay() {
    return OfficeHoursDateHelper::isWeekDay($this->day);
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
   *   TRUE if the given time period is in range, else FALSE.
   */
  public function isInRange(int $from, int $to): bool {
    if ($to < $from || $to < 0) {
      // @todo Error. Raise try/catch exception for $to < $from.
      // @todo Undefined result for <0. Raise try/catch exception.
      return FALSE;
    }

    // Convert $from-$to dates to weekdays.
    // @todo Support other first_day_of_week.
    if (!OfficeHoursDateHelper::isWeekDay($to)) {
      $from = OfficeHoursDateHelper::getWeekday($from);
      $to = OfficeHoursDateHelper::getWeekday($to);
    }

    // @todo Use $this->getStatus()?
    $day = $this->getWeekday();
    if ($day == $from - 1 || ($day == $from + 6)) {
      $start = (int) $this->starthours;
      $end = (int) $this->endhours;
      // We were open yesterday evening, check if we are still open.
      // Only check day, not time. For that, use isOpen().
      if ($start > $end) {
        return TRUE;
      }
    }
    elseif ($day >= $from && $day <= $to) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * The opening status of this time slot.
   *
   * @var int
   */
  public const UNDEFINED = -1;
  public const CLOSED_ALL_DAY = 0;
  public const IS_OPEN = 1;
  public const WAS_OPEN = 2;
  public const WILL_OPEN = 3;

  /**
   * Returns if a time slot is currently open or not.
   *
   * @param int $time
   *   A UNIX timestamp. If 0, set to 'REQUEST_TIME', alter-hook for Timezone.
   *
   * @return int
   *   a predefined constant with the status.
   */
  public function getStatus(int $time = 0): int {
    $status = static::UNDEFINED;

    $time = OfficeHoursDateHelper::getRequestTime(0, $this->getParent());
    $now_weekday = OfficeHoursDateHelper::getWeekday($time);
    // 'Hi' format, with leading zero (0900).
    $now = OfficeHoursDateHelper::format($time, 'Hi');

    $slot = $this->getValue();
    // Normalize to exception/season to weekday.
    $item_weekday = $this->getWeekday();
    $start = (int) $slot['starthours'];
    $end = (int) $slot['endhours'];

    // Check for Weekday and for Exception day ('midnight').
    if ($item_weekday == $now_weekday - 1 || ($item_weekday == $now_weekday + 6)) {
      // We were open yesterday evening, check if we are still open.
      if ($start >= $end && $end > $now) {
        $status = static::IS_OPEN;
      }
    }
    elseif ($item_weekday == $now_weekday) {

      if (($slot['starthours'] === NULL) && ($slot['endhours'] === NULL)) {
        // We are closed all day.
        // (Do not use $start and $end, which are integers.)
        $status = static::CLOSED_ALL_DAY;
      }
      elseif (($start < $end) && ($end < $now)) {
        // We were open today, but are already closed.
        $status = static::WAS_OPEN;
      }
      elseif ($start > $now) {
        // We will open later today.
        $status = static::WILL_OPEN;
      }
      else {
        // We were open today, check if we are still open.
        if (
          ($start > $end) // We are open until after midnight.
          || ($end == 0) // We are open until midnight (24:00 or empty).
          || ($start == $end && !is_null($start)) // We are open 24hrs per day.
          || (($start < $end) && ($end > $now)) // We are open, normal time slot.
        ) {
          // We are open.
          $status = static::IS_OPEN;
        }
      }
    }

    return $status;
  }

  /**
   * Returns if a time slot is currently open or not.
   *
   * @param int $time
   *   A timestamp. Might be adapted for User Timezone.
   *
   * @return bool
   *   TRUE if open at $time.
   */
  public function isOpen(int $time) {
    $status = $this->getStatus($time);
    return $status == static::IS_OPEN;
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
    // @todo Use $item->isEmpty(), but that gives other result, somehow.
    if (!isset($value['day']) && !isset($value['time'])) {
      return TRUE;
    }

    // If all_day is set, day is not empty.
    if ($value['all_day'] ?? FALSE) {
      return FALSE;
    }

    // Facilitate closed Exception days - first slots are never empty.
    if (OfficeHoursDateHelper::isExceptionDay($value['day'])) {
      switch ($value['day_delta']) {
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
    $value = $this->format($value);
    parent::setValue($value, $notify);
  }

  /**
   * Normalizes the contents of the Item.
   *
   * @param array|null $value
   *   The value of a time slot; day, start, end, comment.
   *
   * @return array
   *   The normalized value of a time slot.
   */
  public static function format($value) {
    $value ??= [];
    $day = $value['day'] ?? NULL;
    $day_delta = $value['day_delta'] ?? 0;

    if (OfficeHoursDateHelper::isExceptionHeader($day)) {
      // An ExceptionItem is created with ['day' => EXCEPTION_DAY_MIN,].
      $day = NULL;
      $value = [];
    }

    // Set default values for new, empty widget.
    if ($day === NULL) {
      $value += [
        'day' => '',
        'day_delta' => 0,
        'all_day' => FALSE,
        'starthours' => NULL,
        'endhours' => NULL,
        'comment' => '',
      ];
      return $value;
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

    $formatted_time = "$start$separator$end";
    \Drupal::moduleHandler()->alter('office_hours_time_format', $formatted_time);

    return $formatted_time;
  }

  /**
   * Returns the translated label of a Weekday/Exception day, e.g., 'tuesday'.
   *
   * @param string $pattern
   *   The day/date formatting pattern.
   * @param array $value
   *   An Office hours value structure.
   * @param int $day_delta
   *   An optional day_delta.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The formatted day label, e.g., 'tuesday'.
   */
  public static function formatLabel(string $pattern, array $value, $day_delta = 0) {
    $label = '';

    $day = $value['day'];
    switch (TRUE) {
      case ($day_delta):
        // Return fast for a following slot.
        $label = t('and');
        break;

      case ($pattern == 'none'):
        // Return fast if weekday is not to be displayed.
        $label = '';
        break;

      case ($day === '' || $day === NULL || $day < 0):
        // A new Exception slot.
        $label = '';
        break;

      case OfficeHoursDateHelper::isSeasonHeader($day):
      case OfficeHoursDateHelper::isSeasonDay($day):
      case OfficeHoursDateHelper::isWeekDay($day):
        // The day number is a weekday number + optional Season ID.
        $label = OfficeHoursDateHelper::weekDaysByFormat($pattern, $day);
        break;

      case OfficeHoursDateHelper::isExceptionDay($day) && ($pattern == 'l'):
        // Convert date into weekday in widget.
        $label = OfficeHoursDateHelper::format($day, $pattern);
        break;

      case OfficeHoursDateHelper::isExceptionDay($day):
        $label = OfficeHoursDateHelper::format($day, $pattern);
        break;

      default:
        // This is an error. Use $item->label() instead,
        // and make sure the class is OK.
        // @todo Add Watchdog message when we land here.
        $label = '';
        break;

    }
    return $label;
  }

  /**
   * Formats the labels of a Render element.
   *
   * @param array $settings
   *   The formatter settings.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The translated formatted day label, or NULL if there is no label defined.
   */
  public function label(array $settings) {
    $value = $this->getValue();
    $day = $value['day'];
    $day_delta = $value['day_delta'];

    // Get the label.
    $pattern = $settings['day_format'];
    $label = OfficeHoursItem::formatLabel($pattern, $value, $day_delta);

    // Extend the label for Grouped days.
    if (isset($value['endday'])) {
      $day = $value['endday'];
      $label_to = $this->formatLabel($pattern, ['day' => $day]);

      $group_separator = $settings['separator']['grouped_days'];
      $label .= "$group_separator$label_to";
    }

    // Add separator after day name. E.g., 'Monday' --> 'Monday: '.
    $days_suffix = $settings['separator']['day_hours'] ?? '';
    $label .= $days_suffix;

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getSeason() {
    $seasons = $this->parent->getSeasons(TRUE, FALSE);
    $season = $seasons[$this->getSeasonId()];
    return $season;
  }

  /**
   * Gets the weekday number.
   *
   * @return int
   *   Returns the weekday number(0=Sun, 6=Sat).
   */
  public function getWeekday() {
    return OfficeHoursDateHelper::getWeekday($this->day);
  }

}
