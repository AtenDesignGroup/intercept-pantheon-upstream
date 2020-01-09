<?php

namespace Drupal\intercept_core\Utility;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * A helper utility for Date object information.
 */
class Dates {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new Dates object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Calculate the duration in minutes between two dates.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date1
   *   The first DrupalDateTime object.
   * @param \Drupal\Core\Datetime\DrupalDateTime $date2
   *   The second DrupalDateTime object.
   *
   * @return int
   *   The duration in minutes.
   */
  public static function duration(DrupalDateTime $date1, DrupalDateTime $date2) {
    $total = 0;
    if ($int = self::interval($date1, $date2)) {
      $hours = $int->h;
      if ($int->days > 0) {
        $hours += ($int->days * 24);
      }
      $total = ($hours * 60) + $int->i;
    }
    return $total;
  }

  /**
   * Gets the difference between two DrupalDateTime objects.
   *
   * @return \DateInterval|false
   *   A DateInterval object, or FALSE.
   */
  public static function interval(DrupalDateTime $date1, DrupalDateTime $date2) {
    if ($date1 > $date2) {
      return FALSE;
    }
    return $date1->diff($date2);
  }

  /**
   * Get date storage format string.
   *
   * @return string
   *   The date storage format.
   */
  public static function storageFormat() {
    return DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
  }

  /**
   * Alias for static::storageFormat()
   *
   * @return string
   *   The date storage format.
   */
  public function getStorageFormat() {
    return self::storageFormat();
  }

  /**
   * Get a timezone object.
   *
   * @param string $name
   *   PHP Timezone name.
   *
   * @return \DateTimeZone
   *   The DateTimeZone object.
   */
  protected function getTimezone($name = 'UTC') {
    if ($name == 'default') {
      $config = $this->configFactory->get('system.date');
      $name = $config->get('timezone.default');
    }
    if ($name == 'storage') {
      $name = DateTimeItemInterface::STORAGE_TIMEZONE;
    }
    return new \DateTimeZone($name);
  }

  /**
   * Gets the UTC timezone object.
   *
   * @return \DateTimeZone
   *   The DateTimeZone object.
   */
  public function getUtcTimezone() {
    return $this->getTimezone();
  }

  /**
   * Gets the date field storage timezone.
   *
   * @return \DateTimeZone
   *   The DateTimeZone object.
   */
  public function getStorageTimezone() {
    return $this->getTimezone('storage');
  }

  /**
   * Gets the default site timezone object.
   *
   * @return \DateTimeZone
   *   The DateTimeZone object.
   */
  public function getDefaultTimezone() {
    return $this->getTimezone('default');
  }

  /**
   * Creates a DateTime object.
   *
   * @param string $time
   *   A date/time string.
   * @param string $timezone
   *   A DateTimeZone object representing the timezone of $time.
   *
   * @return \DateTime
   *   The DateTime object.
   */
  public function getDate($time, $timezone = 'storage') {
    return new \DateTime($time, $this->getTimezone($timezone));
  }

  /**
   * Creates a DrupalDateTime object.
   *
   * @param string $time
   *   A date/time string.
   * @param string $timezone
   *   A DateTimeZone object representing the timezone of $time.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The DrupalDateTime object.
   */
  public function getDrupalDate($time, $timezone = 'storage') {
    return DrupalDateTime::createFromDateTime($this->getDate($time, $timezone));
  }

  /**
   * Converts the timezone for a date object.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime|\DateTime $date
   *   The DrupalDateTime or DateTime object.
   * @param string $new_timezone
   *   PHP Timezone name.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The converted DrupalDateTime object.
   */
  public function convertTimezone($date, $new_timezone = 'UTC') {
    $new_date = clone $date;
    $new_date->setTimezone($this->getTimezone($new_timezone));
    return $new_date;
  }

  /**
   * Converts a date string to the system timezone.
   *
   * @param string $string
   *   The datetime string.
   * @param bool $from_default
   *   TRUE if converting from default to UTC, FALSE if opposite.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The converted DrupalDateTime object.
   */
  public function convertDate($string, $from_default = TRUE) {
    $from = $from_default ? $this->getDefaultTimezone() : $this->getUtcTimezone();
    $to = $from_default ? $this->getUtcTimezone() : $this->getDefaultTimezone();
    $date = new DrupalDateTime($string, $from);
    $date->setTimezone($to);
    return $date;
  }

  /**
   * Creates a DateTime object based on a keyed array of date parts.
   *
   * @param int $year
   *   The year formatted as PHP 'Y'.
   * @param int $month
   *   The month formatted as PHP 'n'.
   * @param int $day
   *   The day formatted as PHP 'j'.
   * @param int $hour
   *   The hour formatted as PHP 'H'.
   * @param int $minute
   *   The minute formatted as PHP 'i'.
   * @param int $second
   *   The second formatted as PHP 's'.
   */
  public function createDateFromArray($year = NULL, $month = 1, $day = 1, $hour = 12, $minute = 0, $second = 0) {
    $now = new \DateTime();
    $year = $year ?: $now->format('Y');
    $partsArray = [
      'year' => $year,
      'month' => $month,
      'day' => $day,
      'hour' => $hour,
      'minute' => $minute,
      'second' => $second,
    ];
    return DrupalDateTime::createFromArray($partsArray);
  }

}
