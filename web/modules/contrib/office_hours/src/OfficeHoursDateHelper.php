<?php

namespace Drupal\office_hours;

use Drupal\Core\Datetime\DateHelper;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface;

/**
 * Defines lots of helpful functions for use in massaging dates.
 *
 * For formatting options, see http://www.php.net/manual/en/function.date.php.
 *
 * @todo Centralize here all calls to date().
 * @todo Centralize here all calls to format().
 * @todo Centralize here all calls to strtotime().
 */
class OfficeHoursDateHelper extends DateHelper {

  /**
   * The number of days per week.
   *
   * @var int
   */
  public const DAYS_PER_WEEK = 7;

  /**
   * Defines the format that dates should be stored in.
   *
   * @var \Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATE_STORAGE_FORMAT;
   */
  public const DATE_STORAGE_FORMAT = 'Y-m-d';

  /**
   * The maximum day number for Weekday days.
   *
   * @var int
   */
  protected const WEEK_DAY_MAX = 7;

  /**
   * The Factor for a Season ID (100, 200, ...)
   *
   * @var int
   */
  public const SEASON_ID_FACTOR = 100;

  /**
   * The minimum day number for Seasonal days.
   *
   * Usage: $items->appendItem(['day' => OfficeHoursDateHelper::EXCEPTION_DAY_MIN]).
   * Also used for SeasonHeader: $day = SeasonId + SEASON_DAY_MIN;
   *
   * @var int
   */
  public const SEASON_DAY_MIN = 9;

  /**
   * The maximum day number for Seasonal weekdays.
   *
   * @var int
   */
  public const SEASON_DAY_MAX = 100000000;

  /**
   * The minimum day number for Exception days.
   *
   * @var int
   */
  public const EXCEPTION_DAY_MIN = 100000001;

  /**
   * Returns timestamp for current request. May be adapted for User Timezone.
   *
   * @param int|null $time
   *   The actual UNIX date/timestamp to use.
   *     If set, do nothing.
   *     If not, take REQUEST_TIME and allow hook to use some timezone field.
   *
   * @param \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface|null $items
   *   The Itemlist::getEntity(), that may have a city, timezone, to get time.
   *
   * @return int
   *   A Unix timestamp.
   *
   * @see hook_office_hours_current_time_alter
   * @see \Drupal\Component\Datetime\TimeInterface
   */
  public static function getRequestTime(int $time = 0, OfficeHoursItemListInterface|null $items = NULL) {
    if (!$time) {
      $time = \Drupal::time()->getRequestTime();
      // Call hook. Allows to alter the current time using a timezone.
      $entity = $items ? $items->getEntity() : NULL;
      \Drupal::moduleHandler()->alter('office_hours_current_time', $time, $entity);
    }
    return $time;
  }

  /**
   * Gets the weekday number from a Item->day number.
   *
   * @param int $day
   *   Weekday, Season day or Exception date (Unix timestamp).
   *
   * @return int
   *   The weekday number(0=Sun, 6=Sat) as integer.
   */
  public static function getWeekday($day) {
    if (OfficeHoursDateHelper::isWeekDay($day)) {
      // Regular weekday.
      return $day;
    }
    elseif ($day < OfficeHoursDateHelper::EXCEPTION_DAY_MIN) {
      // Seasonal Weekday: 200...206 -> 0..6 .
      return $day % OfficeHoursDateHelper::SEASON_ID_FACTOR;
    }
    else {
      // Exception date, Unix timestamp.
      // Converted to integer to get '0' for Sunday, not 'false'.
      return (int) idate('w', $day);
    }
  }

  /**
   * Gets the day number of first day of the week.
   *
   * @param string $first_day
   *   First day of week. Optional. If set, this number will be returned.
   *
   * @return int
   *   Returns the first day of the week.
   */
  public static function getFirstDay($first_day = '') {
    if ($first_day === '') {
      $first_day = \Drupal::config('system.date')->get('first_day');
    }
    return $first_day;
  }

  /**
   * Gets the proper format_date() format from the settings.
   *
   * For formatting options, see http://www.php.net/manual/en/function.date.php.
   *
   * @param string $time_format
   *   Time format.
   *
   * @return string
   *   Returns the time format.
   */
  public static function getTimeFormat($time_format) {
    switch ($time_format) {
      case 'G':
        // 24hr without leading zero.
        $time_format = 'G:i';
        break;

      case 'H':
        // 24hr with leading zero.
        $time_format = 'H:i';
        break;

      case 'g':
        // 12hr am/pm without leading zero.
        $time_format = 'g:i a';
        break;

      case 'h':
        // 12hr am/pm with leading zero.
        $time_format = 'h:i a';
        break;
    }
    return $time_format;
  }

  /**
   * Converts a time to a given format.
   *
   * There are too many similar functions:
   *  - \Drupal::service('date.formatter')->format();
   *  - DrupalDateTime->format()
   *  - OfficeHoursDateHelper::format();
   *  - OfficeHoursItem::format();
   *  - OfficeHoursWidgetBase::massageFormValues();
   *
   * For formatting options:
   *
   *   @see https://www.php.net/manual/en/function.date.php
   *   @see https://www.php.net/manual/en/datetime.formats.time.php
   *
   * @todo Use Core/TypedData/ComplexDataInterface.
   *
   * @param string|array $element
   *   A string or array with time element.
   *   Time, in 24hr format '0800', '800', '08:00', '8:00' or empty.
   * @param string $time_format
   *   The requested time format.
   * @param bool $is_end_time
   *   TRUE if the time is an End time of a time slot.
   *
   * @return string
   *   The formatted time, e.g., '08:00'.
   */
  public static function format($element, $time_format, $is_end_time = FALSE) {
    // Be prepared for Datetime and Numeric input.
    // Numeric input set in validateOfficeHoursSlot().
    if (!isset($element)) {
      return NULL;
    }

    static $formatter = NULL;
    // Avoid PHP8.2 Fatal error: Constant expression contains invalid operations
    $formatter ??= \Drupal::service('date.formatter');

    // Normalize $element into a 4-digit time.
    if (is_array($element) && array_key_exists('time', $element)) {
      // HTML5 'datetime' element.
      // Return NULL or time string.
      $time = $element['time'];
    }
    elseif (is_array($element) && array_key_exists('hour', $element)) {
      // SelectList 'datelist' element.
      $time = '';
      if (($element['hour'] !== '') || ($element['minute'] !== '')) {
        $hour = intval($element['hour']);
        $minute = intval($element['minute']);
        // Begin copy DateList::valueCallback().
        if (isset($element['ampm'])) {
          if ($element['ampm'] == 'pm' && $hour < 12) {
            $hour += 12;
          }
          elseif ($element['ampm'] == 'am' && $hour == 12) {
            $hour -= 12;
          }
          unset($element['ampm']);
        }
        /*
        try {
          $date = DrupalDateTime::createFromArray($element, $timezone = NULL);
        } catch (\Exception $e) {
          $form_state->setError($element, t('Selected combination of hour and minute is not valid.'));
        }
         */
        // End copy DateList::valueCallback().
        $time = $hour * 100 + $minute;
      }
    }
    else {
      // String.
      $time = $element;
    }

    if ($time === NULL || $time === '') {
      return NULL;
    }

    if (self::isValidDate($time)) {
      // if (OfficeHoursDateHelper::isExceptionDay($day)) {
      // A Unix date+timestamp.
      switch ($time_format) {
        case 'l':
          // Convert date into weekday in widget.
          $formatted_time = $formatter->format($time, 'custom', $time_format);
          break;

        case 'long':
          // On field settings admin/structure/types/manage/TYPE/display page.
          $formatted_time = $formatter->format($time, $time_format);
          // Remove excessive time part.
          $formatted_time = str_replace(' - 00:00', '', $formatted_time);
          break;

        default:
          $date = DrupalDateTime::createFromTimestamp($time);
          $formatted_time = $date->format($time_format);
          break;
      }
      return $formatted_time;
    }
    elseif (!strstr($time, ':')) {
      // Normalize time to '09:00' format before creating DateTime object.
      try {
        $time = substr("0000$time", -4);
        $date = DrupalDateTime::createFromFormat('Hi', $time);
      }
      catch (\Exception $e) {
        $time = substr("0000$time", -4);
        $hour = substr($time, 0, -2);
        $min = substr($time, -2);
        $time = "$hour:$min";
        $date = new DrupalDateTime($time);
      }
    }
    else {
      $date = new DrupalDateTime($time);
    }

    $formatted_time = $date->format($time_format);
    // Format the 24-hr end time from 0 to 24:00/2400 using a trick.
    if ($is_end_time && strpbrk($time_format, 'GH')) {
      if ($date->format('Hi') === '0000') {
        $date->setTime(23, 00);
        $formatted_time = str_replace('23', '24', $date->format($time_format));
      }
    }

    return $formatted_time;
  }

  /**
   * Gets the (limited) hours of a day.
   *
   * Mimics DateHelper::hours() function, but that function
   * does not support limiting the hours. The limits are set
   * in the Widget settings form, and used in the Widget form.
   *
   * {@inheritdoc}
   */
  public static function hours($time_format = 'H', $required = FALSE, $start = 0, $end = 23) {
    $hours = [];

    // Get the valid hours. DateHelper API doesn't provide
    // straight method for this.
    $add_midnight = empty($end);
    $start = (empty($start)) ? 0 : max(0, (int) $start);
    $end = (empty($end)) ? 23 : min(23, (int) $end);
    $with_zeroes = in_array($time_format, ['H', 'h']);
    $ampm = in_array($time_format, ['g', 'h']);

    // Begin modified copy from date_hours().
    if ($ampm) {
      // 12-hour format.
      $min = 1;
      $max = 24;
      for ($i = $min; $i <= $max; $i++) {
        if ((($i >= $start) && ($i <= $end)) || ($end - $start >= 11)) {
          $hour = ($i <= 12) ? $i : $i - 12;
          $hours[$hour] = $hour < 10 && ($with_zeroes) ? "0$hour" : (string) $hour;
        }
      }
      $hours = array_unique($hours);
    }
    else {
      // 24-hour format.
      $min = $start;
      $max = $end;
      for ($i = $min; $i <= $max; $i++) {
        $hour = $i;
        $hours[$hour] = $hour < 10 && ($with_zeroes) ? "0$hour" : (string) $hour;
      }
    }
    if ($add_midnight) {
      $hour = 0;
      $hours[$hour] = $hour < 10 && ($with_zeroes) ? "0$hour" : (string) $hour;
    }

    $none = ['' => ''];
    $hours = $required ? $hours : $none + $hours;
    // End modified copy from date_hours().
    return $hours;
  }

  /**
   * Gets the UNIX timestamp for today.
   *
   * @param int $time
   *   A UNIX timestamp. If 0, set to 'REQUEST_TIME', alter-hook for Timezone.
   *
   * @return int
   *   The UNIX timestamp for today.
   *
   * @todo Calculate today() for given time.
   */
  public static function today($time = 0) {
    // $time ??= \Drupal::time()->getRequestTime();
    // $date = OfficeHoursDateHelper::format($time, 'Y-m-d');
    // $today = strtotime($date);
    // or $timestamp = strtotime('today midnight');
    // or $date = new DateTime('today midnight');
    // +  $timestamp = $date->getTimestamp();
    $today = strtotime('today midnight');

    return $today;
  }

  /**
   * Initializes day names, using date_api as key (0=Sun, 6=Sat).
   *
   * Be careful: date_api uses PHP: 0=Sunday and DateObject uses ISO: 1=Sunday.
   *
   * @param string $format
   *   The requested format.
   * @param int|null $day
   *   (Optional) A day number.
   *
   * @return array
   *   A list of weekdays in the requested format,
   *   or the requested weekday, if $day is an integer.
   */
  public static function weekDaysByFormat($format, $day = NULL) {
    $days = [];
    switch ($format) {
      case 'number':
        $days = range(1, 7);
        break;

      case 'none':
        $days = array_fill(0, 7, '');
        break;

      case 'long':
        $days = self::weekDays(TRUE);
        break;

      case 'long_untranslated':
        $days = self::weekDaysUntranslated();
        break;

      case 'two_letter':
        // @todo Avoid translation from English to XX, in case of 'Microdata'.
        $days = self::weekDaysAbbr2(TRUE);
        break;

      case 'short':
        // three-letter.
      default:
        $days = self::weekDaysAbbr(TRUE);
        break;
    }

    if ($day === NULL) {
      return $days;
    }

    // Handle Regular/Seasonal Weekdays: $day 200...206 -> 0..6 .
    $day = OfficeHoursDateHelper::getWeekday($day);
    // SeasonHeader has weekday 109 -> 9, so cannot be found.
    return $days[$day] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public static function weekDaysOrdered($office_hours, $first_day = '') {
    $new_office_hours = [];

    // Do an initial re-sort on day number for Weekdays and Exception days.
    // Removed. Already done at loading in OfficeHoursItemList::setValue().
    // ksort($office_hours);
    // Fetch first day of week from field settings, if not given already.
    $first_day = OfficeHoursDateHelper::getFirstDay($first_day);

    // Sort Weekdays. Leave Exception days at bottom of list.
    // Copying to new array to preserve keys.
    for ($i = $first_day; $i <= OfficeHoursDateHelper::DAYS_PER_WEEK; $i++) {
      // Rescue the element to be moved.
      if (isset($office_hours[$i])) {
        $new_office_hours[$i] = $office_hours[$i];
        // Remove this week day from the old array.
        unset($office_hours[$i]);
      }
    }

    return $new_office_hours + $office_hours;
  }

  /**
   * Returns whether the day number is a valid Date.
   *
   * @param int $day
   *   An Office hours 'day' number.
   *
   * @return bool
   *   True if the day_number is a date (UNIX timestamp).
   */
  public static function isValidDate($day) {
    return is_numeric($day)
      && ($day >= OfficeHoursDateHelper::EXCEPTION_DAY_MIN
      || $day < 0);
  }

  /**
   * Returns whether the day number is an Exception day.
   *
   * @param int $day
   *   The Office hours 'day' element as weekday or Exception day date.
   * @param bool $include_empty_day
   *   Set to TRUE if the 'Add exception' empty day is also an Exception day.
   *
   * @return bool
   *   True if the day_number is a date (UNIX timestamp).
   */
  public static function isExceptionDay($day, $include_empty_day = FALSE) {
    // Do NOT convert to integer, since day may be empty.
    if ($include_empty_day && $day === '') {
      return TRUE;
    }
    if ($day == 'exception_day_delta') {
      // A following slot on an exception day.
      return TRUE;
    }
    return self::isValidDate($day);
  }

  /**
   * Returns if the day number is the inserted Exception header.
   *
   * @param int $day
   *   The Office hours 'day' element as weekday or Exception day date.
   *
   * @return bool
   *   True if the day_number is EXCEPTION_DAY_MIN.
   */
  public static function isExceptionHeader($day) {
    return $day == OfficeHoursDateHelper::EXCEPTION_DAY_MIN;
  }

  /**
   * Determines whether the item is a seasonal or a regular Weekday.
   *
   * @param int $day
   *   The Office hours 'day' element as Weekday/SeasonDay
   *   (using date_api as key (0=Sun, 6=Sat)) or Exception day date.
   *
   * @return int
   *   The season ID.
   */
  public static function getSeasonId($day) {
    $season_id = OfficeHoursDateHelper::isSeasonDay($day)
      ? $day - $day % OfficeHoursDateHelper::SEASON_ID_FACTOR
      : 0;
    return $season_id;
  }

  /**
   * Determines whether the item is a seasonal or a regular Weekday.
   *
   * @param int $day
   *   The Office hours 'day' element as weekday
   *   (using date_api as key (0=Sun, 6=Sat)) or Exception day date.
   *
   * @return bool
   *   True if the day_number is a seasonal weekday (100 to 100....7).
   */
  public static function isSeasonDay($day) {
    return $day >= OfficeHoursDateHelper::SEASON_DAY_MIN
      && $day <= OfficeHoursDateHelper::SEASON_DAY_MAX;
  }

  /**
   * Determines whether the item is a season header.
   *
   * @param int $day
   *   The Office hours 'day' element as weekday
   *   (using date_api as key (0=Sun, 6=Sat)), season day or Exception date.
   *
   * @return int
   *   0 if the Item is a regular Weekday, E.g., 1..9 -> 0.
   *   season_id if a seasonal weekday, E.g., 301..309 -> 100..100.
   */
  public static function isSeasonHeader($day) {
    $result = (intval($day) % OfficeHoursDateHelper::SEASON_ID_FACTOR) == OfficeHoursDateHelper::SEASON_DAY_MIN;
    return $result;
  }

  /**
   * Determines whether the item is a seasonal or a regular Weekday.
   *
   * @param int $day
   *   The Office hours 'day' element as weekday
   *   (using date_api as key (0=Sun, 6=Sat)) or Exception day date.
   *
   * @return bool
   *   True if the day_number is a seasonal weekday (100 to 100....7).
   */
  public static function isWeekDay($day) {
    return $day <= OfficeHoursDateHelper::WEEK_DAY_MAX;
  }

  /**
   * Creates a date object from an array of date parts.
   *
   * Wrapper function to centralize all Date/Time functions into this class.
   *
   * @param array $date_parts
   *   Date parts for datetime.
   * @param int|null $timezone
   *   Timezone for datetime.
   * @param array $settings
   *   Optional settings.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   A new DateTimePlus object.
   */
  public static function createFromArray(array $date_parts, $timezone = NULL, array $settings = []) {
    return DrupalDateTime::createFromArray($date_parts, $timezone, $settings);
  }

  /**
   * Creates a date object from an input format.
   *
   * Wrapper function to centralize all Date/Time functions into this class.
   *
   * @param string $format
   *   PHP date() type format for parsing the input. This is recommended
   *   to use things like negative years, which php's parser fails on, or
   *   any other specialized input with a known format. If provided the
   *   date will be created using the createFromFormat() method.
   * @param mixed $time
   *   A time.
   * @param mixed $timezone
   *   A timezone.
   * @param array $settings
   *   - validate_format: (optional) Boolean choice to validate the
   *     created date using the input format. The format used in
   *     createFromFormat() allows slightly different values than format().
   *     Using an input format that works in both functions makes it
   *     possible to a validation step to confirm that the date created
   *     from a format string exactly matches the input. This option
   *     indicates the format can be used for validation. Defaults to TRUE.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   A new DateTimePlus object.
   *
   * @see http://php.net/manual/datetime.createfromformat.php
   * @see __construct()
   */
  public static function createFromFormat($format, $time, $timezone = NULL, array $settings = []) {
    return DrupalDateTime::createFromFormat($format, $time, $timezone, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromTimestamp($time, $timezone = NULL, array $settings = []) {
    return DrupalDateTime::createFromTimestamp($time, $timezone, $settings);
  }

}
