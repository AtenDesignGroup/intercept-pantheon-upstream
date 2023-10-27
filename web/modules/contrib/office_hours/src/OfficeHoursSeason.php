<?php

namespace Drupal\office_hours;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

/**
 * Defines a 'season'.
 */
class OfficeHoursSeason {
  // @todo Extends Map {.
  /**
   * The Season ID.
   *
   * @var int
   */
  protected $id = 0;
  /**
   * The Season name.
   *
   * @var string
   */
  protected $name = '';
  /**
   * The start date of the season.
   *
   * @var int
   */
  protected $from = 0;
  /**
   * The end date of the season.
   *
   * @var int
   */
  protected $to = 6;

  /**
   * The Factor for a Season ID (100, 200, ...)
   *
   * @var int
   */
  const SEASON_ID_FACTOR = 100;

  /**
   * The default name, label, for a new season.
   *
   * @var string
   */
  const SEASON_DEFAULT_NAME = 'New season';

  use StringTranslationTrait;

  /**
   * OfficeHoursSeason constructor.
   *
   * @param int $var
   *   or: The season ID (100, 200, ...)
   *   or: An OfficeHours Item, read from database.
   *   or: a Season, to be cloned.
   * @param string $name
   *   The season name.
   * @param int $from
   *   The start date of the season (unix timestamp).
   * @param int $to
   *   The end date of the season (unix timestamp).
   */
  public function __construct($var = 0, $name = '', $from = 0, $to = 0) {
    if (is_array($var)) {
      $this->id = $var['id'];
      $this->name = $var['name'];
      $this->from = $var['from'];
      $this->to = $var['to'];
    }
    elseif ($var instanceof OfficeHoursSeason) {
      /** @var \Drupal\office_hours\OfficeHoursSeason $var */
      $this->id = $var->id();
      $this->name = $var->getName();
      $this->from = $var->getFromDate();
      $this->to = $var->getToDate();
    }
    elseif ($var instanceof OfficeHoursItem) {
      /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $var */
      $this->id = $var->getSeasonId();
      $this->name = $var->getValue()['comment'];
      $this->from = $var->getValue()['starthours'];
      $this->to = $var->getValue()['endhours'];
    }
    else {
      $id = $var;
      $this->id = $id;
      $this->name = $name;
      $this->from = $from;
      // If season ID is 0, then end-weekday = 6 for regular weekdays.
      $this->to = ($id) ? $to : $this->to;
    }
    if (!is_numeric($this->from)) {
      $this->from = strtotime($this->from);
    }
    if (!is_numeric($this->to)) {
      $this->to = strtotime($this->to);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $values = [];
    foreach ($this as $name => $property) {
      $values[$name] = $property;
    }
    return $values;
  }

  /**
   * Creates a date object from an array of date parts.
   *
   * @param array $value
   *   The Time slot to be manipulated, or
   *   Empty, if the season data must be encoded as time slot.
   *
   * @return array
   *   An array, formatted as a time slot.
   */
  public function toTimeSlotArray(array $value = []) {

    if ($value) {
      // Return the manipulated time slot.
      // In order to save the time slot in the database,
      // we need to have a unique day_number.
      // Solution: add the season ID (100, 200, ...) to the day (0..6).
      $value['day'] += $this->id;
    }
    else {
      // Return the season header as time slot.
      // From and To are Unix timestamps.
      // Solution: assign it the special day number 9 + Season ID.
      $value = [
        'day' => OfficeHoursItem::SEASON_DAY + $this->id,
        'all_day' => FALSE,
        'starthours' => $this->from,
        'endhours' => $this->to,
        'comment' => $this->name,
      ];
    }
    return $value;
  }

  /**
   * Determines if the Season is empty.
   *
   * @return bool
   *   TRUE if the season is empty (to be discarded).
   */
  public function isEmpty() {
    if ($this->id() == 0) {
      return TRUE;
    }
    return (!$this->from
      && ($this->name == ''
      || $this->name == $this->t($this::SEASON_DEFAULT_NAME)));
  }

  /**
   * Filter to determine if a date belongs to this season.
   *
   * @param int $day
   *   A unix timestamp.
   *
   * @return bool
   *   TRUE if the date belongs to this season.
   */
  public function isInSeason(int $day) {
    if ($day < OfficeHoursDateHelper::isExceptionDay($day)) {
      return ($day >= $this->getFromDate() && $day <= $this->getToDate());
    }
    else {
      return ($day >= $this->id
        && $day < $this->id + OfficeHoursItem::SEASON_DAY);
    }
  }

  /**
   * Determines whether the item is a season header.
   *
  * @param $day
   *   The Office hours 'day' element as weekday
   *   (using date_api as key (0=Sun, 6=Sat)), season day or Exception date.
   *
   * @return int
   *   0 if the Item is a regular Weekday, E.g., 1..9 -> 0.
   *   season_id if a seasonal weekday, E.g., 301..309 -> 100..100.
   */
  public static function isSeasonHeader($day) {
    $day = (int) $day;
    if ($day === 0) {
      return FALSE;
    }
    return ($day % OfficeHoursSeason::SEASON_ID_FACTOR) == OfficeHoursItem::SEASON_DAY;
  }

  /**
   * Returns the Season ID.
   *
   * @return int
   *   The ID.
   */
  public function id() {
    return $this->id;
  }

  /**
   * Returns the translated Season Name.
   *
   * @return string
   *   The name.
   */
  public function label() {
    if ($this->name) {
      // @todo Translate?
      return $this->name;
    }
    return $this->t($this::SEASON_DEFAULT_NAME);
  }

  /**
   * Returns the untranslated Season name.
   *
   * @return string
   *   The season name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Returns the formatted season start date.
   *
   * @param string $pattern
   *   The string pattern for the date to be returned.
   *
   * @return string|int
   *   The formatted date.
   */
  public function getFromDate($pattern = '') {
    $day = $this->from;

    if (is_numeric($day) && $pattern) {
      // No usage for season 0, normal weekdays.
      if ($day <= OfficeHoursItem::SEASON_MAX_DAY_NUMBER) {
        return '';
      }
      // @todo Use OfficeHoursDateHelper::getLabel($pattern, ['day' => $result]).
      // return OfficeHoursDateHelper::getLabel($pattern, ['day' => $day]);
      return \Drupal::service('date.formatter')->format($day, 'custom', $pattern);
    }
    return $day;
  }

  /**
   * Returns the formatted season end date.
   *
   * @param string $pattern
   *   The string pattern for the date to be returned.
   *
   * @return string
   *   The formatted date.
   */
  public function getToDate($pattern = '') {
    $day = $this->to;

    if (is_numeric($day) && $pattern) {
      // No usage for season 0, normal weekdays.
      if ($day <= OfficeHoursItem::SEASON_MAX_DAY_NUMBER) {
        return '';
      }
      // @todo Use OfficeHoursDateHelper::getLabel($pattern, ['day' => $result]).
      // return OfficeHoursDateHelper::getLabel($pattern, ['day' => $day]);
      return \Drupal::service('date.formatter')->format($day, 'custom', $pattern);
    }
    return $day;
  }

}
