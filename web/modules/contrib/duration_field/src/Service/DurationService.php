<?php

namespace Drupal\duration_field\Service;

use DateInterval;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\duration_field\Exception\InvalidDurationException;
use Drupal\duration_field\Plugin\DataType\Iso8601StringInterface;

/**
 * Provides services for the Duration Field module.
 */
class DurationService implements DurationServiceInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function checkDurationInvalid($duration) {

    if (!empty($duration) && !preg_match(Iso8601StringInterface::DURATION_STRING_PATTERN, $duration)) {
      throw new InvalidDurationException('The submitted duration is not a valid duration string.');
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function convertDateArrayToDurationString(array $input) {

    $duration = '';

    $date_mappings = [
      'y' => 'Y',
      'm' => 'M',
      'd' => 'D',
    ];

    foreach (array_keys($date_mappings) as $key) {
      if (isset($input[$key]) && $input[$key]) {
        $duration .= $input[$key] . $date_mappings[$key];
      }
    }

    $time_mappings = [
      'h' => 'H',
      'i' => 'M',
      's' => 'S',
    ];

    $found = FALSE;
    foreach (array_keys($time_mappings) as $key) {

      if (isset($input[$key]) && $input[$key]) {

        if (!$found) {
          $found = TRUE;
          $duration .= 'T';
        }
        $duration .= $input[$key] . $time_mappings[$key];
      }
    }

    return strlen($duration) ? 'P' . $duration : Iso8601StringInterface::EMPTY_DURATION;
  }

  /**
   * {@inheritdoc}
   */
  public function getDateIntervalFromDurationString($durationString) {

    // Note: this will throw
    // \Drupal\duration_field\Exception\InvalidDurationException if $value is
    // an invalid ISO 8601 Duration string.
    $this->checkDurationInvalid($durationString);

    if (!empty($durationString)) {
      return new DateInterval($durationString);
    }
    else {
      return $this->createEmptyDateInterval();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDurationStringFromDateInterval(DateInterval $dateInterval) {
    return $this->convertDateArrayToDurationString([
      'y' => $dateInterval->format('%y'),
      'm' => $dateInterval->format('%m'),
      'd' => $dateInterval->format('%d'),
      'h' => $dateInterval->format('%h'),
      'i' => $dateInterval->format('%i'),
      's' => $dateInterval->format('%s'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function convertDateArrayToDateInterval(array $input) {

    $duration_string = $this->convertDateArrayToDurationString($input);

    return $this->getDateIntervalFromDurationString($duration_string);
  }

  /**
   * {@inheritdoc}
   */
  public function createEmptyDateInterval() {
    return new DateInterval(Iso8601StringInterface::EMPTY_DURATION);
  }

  /**
   * {@inheritdoc}
   */
  public function getHumanReadableStringFromDateInterval(DateInterval $dateInterval, array $granularity, $separator = ' ', $textLength = 'full') {

    $output = [];
    if ($granularity['y'] && $years = $dateInterval->format('%y')) {
      $output[] = $this->getTimePeriod('year', $years, $textLength);
    }

    if ($granularity['m'] && $months = $dateInterval->format('%m')) {
      $output[] = $this->getTimePeriod('month', $months, $textLength);
    }

    if ($granularity['d'] && $days = $dateInterval->format('%d')) {
      $output[] = $this->getTimePeriod('day', $days, $textLength);
    }

    if ($granularity['h'] && $hours = $dateInterval->format('%h')) {
      $output[] = $this->getTimePeriod('hour', $hours, $textLength);
    }

    if ($granularity['i'] && $minutes = $dateInterval->format('%i')) {
      $output[] = $this->getTimePeriod('minute', $minutes, $textLength);
    }

    if ($granularity['s'] && $seconds = $dateInterval->format('%s')) {
      $output[] = $this->getTimePeriod('second', $seconds, $textLength);
    }

    return count($output) ? implode($separator, $output) : $this->t('Empty');
  }

  /**
   * {@inheritdoc}
   */
  public function getSecondsFromDateInterval(DateInterval $dateInterval) {
    return date_create('@0')->add($dateInterval)->getTimestamp();
  }

  /**
   * {@inheritdoc}
   */
  public function getSecondsFromDurationString($durationString) {
    $date_interval = $this->getDateIntervalFromDurationString($durationString);

    return date_create('@0')->add($date_interval)->getTimestamp();
  }

  /**
   * Returns a human-friendly value for a given time period key.
   *
   * @param string $type
   *   The type of the human-readable value to retrieve.
   * @param int $value
   *   The amount for that time period.
   * @param string $textLength
   *   The length of text to use. Allowed values are 'full' and 'short'.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated text value of the given type.
   */
  protected function getTimePeriod($type, $value, $textLength) {

    if ($type == 'year') {
      if ($textLength == 'full') {
        return $this->formatPlural($value, '1 year', '@count years');
      }
      elseif ($textLength == 'short') {
        return $this->formatPlural($value, '1 yr', '@count yr');
      }
    }
    elseif ($type == 'month') {
      if ($textLength == 'full') {
        return $this->formatPlural($value, '1 month', '@count months');
      }
      elseif ($textLength == 'short') {
        return $this->formatPlural($value, '1 mo', '@count mo');
      }
    }
    elseif ($type == 'day') {
      return $this->formatPlural($value, '1 day', '@count days');
    }
    elseif ($type == 'hour') {
      if ($textLength == 'full') {
        return $this->formatPlural($value, '1 hour', '@count hours');
      }
      elseif ($textLength == 'short') {
        return $this->formatPlural($value, '1 hr', '@count hr');
      }
    }
    elseif ($type == 'minute') {
      if ($textLength == 'full') {
        return $this->formatPlural($value, '1 minute', '@count minutes');
      }
      elseif ($textLength == 'short') {
        return $this->formatPlural($value, '1 min', '@count min');
      }
    }
    elseif ($type == 'second') {
      if ($textLength == 'full') {
        return $this->formatPlural($value, '1 second', '@count seconds');
      }
      elseif ($textLength == 'short') {
        return $this->formatPlural($value, '1 s', '@count s');
      }
    }
  }

}
