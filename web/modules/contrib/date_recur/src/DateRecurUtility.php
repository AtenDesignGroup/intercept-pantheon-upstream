<?php

declare(strict_types = 1);

namespace Drupal\date_recur;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Provide standalone utilities.
 */
class DateRecurUtility {

  /**
   * Get the smallest date given a granularity and input.
   *
   * @param string $granularity
   *   The granularity of the input.
   * @param string $value
   *   User date input.
   * @param \DateTimeZone $timezone
   *   The timezone of the input.
   *
   * @return \DateTime
   *   A date time with the smallest value given granularity and input.
   */
  public static function createSmallestDateFromInput(string $granularity, string $value, \DateTimeZone $timezone): \DateTime {
    return static::createDateFromInput($granularity, $value, $timezone, 'start');
  }

  /**
   * Get the largest date given a granularity and input.
   *
   * @param string $granularity
   *   The granularity of the input.
   * @param string $value
   *   User date input.
   * @param \DateTimeZone $timezone
   *   The timezone of the input.
   *
   * @return \DateTime
   *   A date time with the smallest value given granularity and input.
   */
  public static function createLargestDateFromInput(string $granularity, string $value, \DateTimeZone $timezone): \DateTime {
    return static::createDateFromInput($granularity, $value, $timezone, 'end');
  }

  /**
   * Get the smallest or largest date given a granularity and input.
   *
   * @param string $granularity
   *   The granularity of the input. E.g 'year', 'month', etc.
   * @param string $value
   *   User date input.
   * @param \DateTimeZone $timezone
   *   The timezone of the input.
   * @param string $end
   *   Either 'start' or 'end' to get a date at the beginning or end of a
   *   granularity period.
   *
   * @return \DateTime
   *   A date time with the smallest value given granularity and input.
   *
   * @internal
   */
  protected static function createDateFromInput(string $granularity, string $value, \DateTimeZone $timezone, string $end): \DateTime {
    assert(in_array($end, ['start', 'end']));
    $start = $end === 'start';

    $granularityFormatsMap = DateRecurGranularityMap::GRANULARITY_DATE_FORMATS;
    $format = $granularityFormatsMap[$granularity];

    // PHP fills missing granularity parts with current datetime. Use this
    // object to reconstruct the date at the beginning of the granularity
    // period.
    $knownDate = \DateTime::createFromFormat($format, $value, $timezone);

    $granularityComparison = DateRecurGranularityMap::GRANULARITY;
    $granularityInt = $granularityComparison[$granularity];

    $dateParts = [
      'year' => (int) $knownDate->format('Y'),
      'month' => $granularityInt >= 2 ? (int) $knownDate->format('m') : ($start ? 1 : 12),
      'day' => $granularityInt >= 3 ? (int) $knownDate->format('d') : 1,
      'hour' => $granularityInt >= 4 ? (int) $knownDate->format('H') : ($start ? 0 : 23),
      'minute' => $granularityInt >= 5 ? (int) $knownDate->format('i') : ($start ? 0 : 59),
      'second' => $granularityInt >= 6 ? (int) $knownDate->format('s') : ($start ? 0 : 59),
    ];

    $date = DrupalDateTime::createFromArray($dateParts, $knownDate->getTimezone());

    // Getting the last day of a month is a little more complex. Use the created
    // date to get number of days in the month.
    if (!$start && $granularityInt < 3) {
      $dateParts['day'] = $date->format('t');
      $date = DrupalDateTime::createFromArray($dateParts, $date->getTimezone());
    }

    return $date->getPhpDateTime();
  }

}
