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
   *   season_id if a seasonal weekday, E.g., 301..309 -> 100..100.
   */
  public function getSeasonId() {
    $day = $this->getValue()['day'];

    return OfficeHoursItem::isSeasonDay($this->getValue())
      ? $day - $day % 100
      : 0;
  }

  /**
   * Determines whether the item is a seasonal or regular Weekday.
   *
   * @return int
   *   0 if the Item is a regular Weekday,E.g., 1..9 -> 0.
   *   season_id if a seasonal weekday, E.g., 301..309 -> 100..100.
   */
  public function isSeasonHeader() {
    return OfficeHoursSeason::isSeasonHeader($this->getValue());
  }

  /**
   * Determines whether the item is a Weekday or an Exception day.
   *
   * @return bool
   *   TRUE if the item is Exception day, FALSE otherwise.
   */
  public function isException() {
    return FALSE;
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
   *   The value of a time slot; day, start, end, comment.
   *   The value 'day_delta' must be added in case of widgets and formatters.
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

    // Check 'day_delta', to facilitate closed Exception days.
    if (OfficeHoursItem::isExceptionDay($value)) {
      switch ($value['day_delta'] ?? 0) {
        case 0:
          // First slot is never empty if an Exception day is set.
          // In this case, on that date, the entity is 'Closed'.
          // Note: day_delta is not set upon load, since not in database.
          // In ExceptionsSlot(Widget), 'day_delta' is added explicitly.
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
   * Returns whether the day number an Exception date.
   *
   * @param array $value
   *   The Office hours data structure, having 'day' as weekday
   *   (using date_api as key (0=Sun, 6=Sat)) or Exception day date.
   * @param bool $include_empty_day
   *   Set to TRUE if the 'add Exception' empty day is also an Exception day.
   *
   * @return bool
   *   True if the day_number is a date (unix timestamp).
   */
  public static function isExceptionDay(array $value, $include_empty_day = FALSE) {
    // Do NOT convert to integer, since day may be empty.
    $day = $value['day'];
    if ($include_empty_day && $day === '') {
      return TRUE;
    }
    return (is_numeric($day) && $day >= OfficeHoursItem::EXCEPTION_DAY);
  }

  /**
   * Determines whether the item is a seasonal or a regular Weekday.
   *
   * @param array $value
   *   The Office hours data structure, having 'day' as weekday
   *   (using date_api as key (0=Sun, 6=Sat)) or Exception day date.
   *
   * @return bool
   *   True if the day_number is a seasonal weekday (100 to 100....7).
   */
  public static function isSeasonDay(array $value) {
    $day = (int) $value['day'];
    return ($day > OfficeHoursSeason::SEASON_DAY
      && $day < OfficeHoursItem::EXCEPTION_DAY);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->formatValue($value);
    parent::setValue($value, $notify);
  }

  /**
   * Normalises the contents of the ItemValue after Widget input.
   *
   * @param array $value
   *   The value of a time slot; day, start, end, comment.
   *
   * @return array
   *   The normalised value of a time slot.
   */
  public static function massageFormValue(array &$value) {
    $value['all_day'] = (bool) ($value['all_day'] ?? FALSE);
    // Format to 'Hi' format, with leading zero (0900).
    $value['starthours'] = OfficeHoursDateHelper::format($value['starthours'] ?? NULL, 'Hi');
    $value['endhours'] = OfficeHoursDateHelper::format($value['endhours'] ?? NULL, 'Hi');

    $day = $value['day'] ?? NULL;
    if ($day && !is_numeric($day)) {
      // When Form is displayed the first time, $day is an integer.
      // When 'Add exception' is pressed, $day is a string "yyyy-mm-dd".
      $value['day'] = strtotime($day);
    }

    return $value;
  }

  /**
   * Normalizes the contents of the Item.
   *
   * @param array $value
   *   The value of a time slot; day, start, end, comment.
   *
   * @return array
   *   The normalised value of a time slot.
   */
  public static function formatValue(array &$value) {
    $day = $value['day'] ?? NULL;
    if ($day == OfficeHoursItem::EXCEPTION_DAY) {
      // An ExceptionItem is created with ['day' => EXCEPTION_DAY,].
      $value = [];
      $day = NULL;
    }

    // Apply default value.
    $value += [
      'day' => '',
      'all_day' => FALSE,
      'starthours' => NULL,
      'endhours' => NULL,
      'comment' => '',
    ];

    if ($day === NULL) {
      // Set default values for new, empty widget.
      return $value;
    }

    if ($day !== '') {
      // Convert day number to integer to get '0' for Sunday, not 'false'.
      $day = (int) $day;
    }

    $starthours = $value['starthours'];
    $endhours = $value['endhours'];
    // Cast the time to integer, to avoid core's error
    // "This value should be of the correct primitive type."
    // This is needed for e.g., '0000' and '0030'.
    $starthours = ($starthours === NULL) ? NULL : (int) $starthours;
    $endhours = ($endhours === NULL) ? NULL : (int) $endhours;

    // Handle the all_day checkbox.
    $all_day = $value['all_day'];
    if ($all_day) {
      $starthours = $endhours = 0;
    }
    elseif ($starthours === 0 && $endhours === 0) {
      $all_day = TRUE;
      $starthours = $endhours = 0;
    }

    $value = [
      'day' => $day,
      'all_day' => $all_day ?? FALSE,
      'starthours' => $starthours,
      'endhours' => $endhours,
      'comment' => $value['comment'],
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

    if (!mb_strlen($start) && !mb_strlen($end)) {
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
    return $this->formatLabel($settings, $this->getValue());
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
    // Return fast if weekday is not to be display.
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
