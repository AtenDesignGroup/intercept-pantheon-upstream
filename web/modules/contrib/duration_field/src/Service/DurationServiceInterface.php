<?php

namespace Drupal\duration_field\Service;

use DateInterval;

/**
 * Interface for classes providing services for the Duration Field module.
 */
interface DurationServiceInterface {

  /**
   * Checks if a given string is a valid ISO 8601 duration string.
   *
   * @param string $duration
   *   The string whose format should be checked.
   *
   * @return bool
   *   - TRUE if the string is a valid ISO 8601 duration string
   *   - FALSE if it's an invalid format
   *
   * @see http://en.wikipedia.org/wiki/Iso8601#Durations
   */
  public function checkDurationInvalid($duration);

  /**
   * Convert array into an ISO 8601 duration string.
   *
   * @param array $input
   *   An array containing the following keys:
   *   - y (year)
   *   - m (month)
   *   - d (day)
   *   - h (hour)
   *   - i (minute)
   *   - s (second)
   *
   * @return string
   *   An ISO 8601 duration string.
   *
   * @see http://en.wikipedia.org/wiki/Iso8601#Durations
   */
  public function convertDateArrayToDurationString(array $input);

  /**
   * Convert an ISO 8601 string into a PHP DateInterval object.
   *
   * @param string $durationString
   *   Ann ISO 8601 duration string.
   *
   * @return \DateInterval
   *   A PHP DateInterval object for the given ISO 8601 duration string.
   *
   * @throws Drupal\duration_field\Exception\InvalidDurationException
   *   Thrown if $value is not a valid ISO 8601 Duration string.
   */
  public function getDateIntervalFromDurationString($durationString);

  /**
   * Convert a PHP DateInterval object to an ISO 8601 duration string.
   *
   * @param array $input
   *   An array containing the following keys:
   *   - y (year)
   *   - m (month)
   *   - d (day)
   *   - h (hour)
   *   - i (minute)
   *   - s (second)
   *
   * @return \DateInterval
   *   A PHP DateInterval object for the given ISO 8601 duration string.
   *
   * @throws Drupal\duration_field\Exception\InvalidDurationException
   *   Thrown if $value is not a valid ISO 8601 Duration string.
   */
  public function convertDateArrayToDateInterval(array $input);

  /**
   * Converts a PHP DateINterval object to an ISO 8601 duration string.
   *
   * @param \DateInterval $dateInterval
   *   A PHP DateInterval object.
   *
   * @return string
   *   The ISO 8601 duration string for the given \DateInterval.
   */
  public function getDurationStringFromDateInterval(DateInterval $dateInterval);

  /**
   * Get a human-readable string representing a DateTime interval.
   *
   * @param \DateInterval $dateInterval
   *   The PHP DateInterval for which a human-readable value should be
   *   extracted.
   * @param array $granularity
   *   An array containing the following keys:
   *   - y (year)
   *   - m (month)
   *   - d (day)
   *   - h (hour)
   *   - i (minute)
   *   - s (second)
   *   Each key should be set to TRUE or FALSE to indicate whether or not the
   *   value should be displayed.
   * @param string $separator
   *   The separator that should be inserted between each time element value of
   *   the interval.
   * @param string $textLength
   *   The length of text that should be returned. Allowed values are 'full' and
   *   'short'.
   *
   * @return string
   *   A human readable translated string representing the DateInterval element.
   */
  public function getHumanReadableStringFromDateInterval(DateInterval $dateInterval, array $granularity, $separator = ' ', $textLength = 'full');

  /**
   * Get the number of seconds a given duration represents.
   *
   * @param \DateInterval $dateInterval
   *   The DateInterval representing the duration.
   *
   * @return int
   *   The number of seconds the interval represents.
   */
  public function getSecondsFromDateInterval(DateInterval $dateInterval);

  /**
   * Get the number of seconds an ISO 8601 duration string represents.
   *
   * @param string $durationString
   *   The ISO 8601 duration string.
   *
   * @return int
   *   The number of seconds the duration string represents.
   */
  public function getSecondsFromDurationString($durationString);

}
