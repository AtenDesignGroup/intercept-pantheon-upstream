<?php

namespace Drupal\intercept_core\Utility;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

class Dates {

  /**
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
   * @return int
   */
  public static function duration(DateTimePlus $date1, DateTimePlus $date2) {
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

  public static function interval(DateTimePlus $date1, DateTimePlus $date2) {
    if ($date1 > $date2) {
      return FALSE;
    }
    return $date1->diff($date2);
  }

  /**
   * Get date storage format string.
   *
   * @return string
   */
  public static function storageFormat() {
    return DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
  }

  /**
   * Alias for static::storageFormat()
   *
   * @return string
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
   * @return \DateTimeZone
   */
  public function getUtcTimezone() {
    return $this->getTimezone();
  }

  /**
   * Date field storage timezone.
   *
   * @return \DateTimeZone
   */
  public function getStorageTimezone() {
    return $this->getTimezone('storage');
  }

  /**
   * Default site timezone.
   *
   * @return \DateTimeZone
   */
  public function getDefaultTimezone() {
    return $this->getTimezone('default');
  }

  /**
   * @param $string
   * @param string $timezone
   * @return \DateTime
   */
  public function getDate($string, $timezone = 'storage') {
    return new \DateTime($string, $this->getTimezone($timezone));
  }

  /**
   * @param $string
   * @param string $timezone
   * @return DrupalDateTime
   */
  public function getDrupalDate($string, $timezone = 'storage') {
    return DrupalDateTime::createFromDateTime($this->getDate($string, $timezone));
  }

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
