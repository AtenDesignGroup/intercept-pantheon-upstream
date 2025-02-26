<?php

namespace Drupal\office_hours;

// The following are not used, but left for testing below issue.
// @see https://www.drupal.org/project/office_hours/issues/3399054
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

/**
 * Defines a 'season'.
 */
class OfficeHoursSeason {
  // @todo Extends Map {.
  // use DependencySerializationTrait;
  // use StringTranslationTrait;

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
   * The default name, label, for a new season.
   *
   * @var string
   */
  protected const SEASON_DEFAULT_NAME = 'New season';

  /**
   * OfficeHoursSeason constructor.
   *
   * @param int|\Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $var
   *   either The season ID (100, 200, ...)
   *   or an OfficeHours Item, read from database.
   *   or a Season, to be cloned.
   * @param string $name
   *   The Season name.
   * @param int $from
   *   The start date of the season (UNIX timestamp).
   * @param int $to
   *   The end date of the season (UNIX timestamp).
   */
  public function __construct($var = 0, $name = '', $from = 0, $to = 0) {

    switch (TRUE) {
      case is_array($var):
        $this->setValue($var);
        break;

      case $var instanceof OfficeHoursSeason:
        /** @var \Drupal\office_hours\OfficeHoursSeason $var */
        $this->id = $var->id();
        $this->name = $var->getName();
        $this->from = $var->getFromDate();
        $this->to = $var->getToDate();
        break;

      case $var instanceof OfficeHoursItem:
        /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $var */
        $this->id = $var->getSeasonId();
        $this->setValue($var->getValue());
        break;

      default:
        $id = $var;
        $this->id = $id;
        $this->name = $name;
        $this->from = $from;
        // If season ID is 0, then end-weekday = 6 for regular weekdays.
        $this->to = ($id) ? $to : $this->to;
        break;

    }

    if ($this->id && $this->name == '') {
      $this->name = $this::SEASON_DEFAULT_NAME;
    }
    if (!is_numeric($this->from)) {
      $this->from = strtotime($this->from);
    }
    if (!is_numeric($this->to)) {
      $this->to = strtotime($this->to);
    }

  }

  /**
   * Returns the submitted and sanitized season values.
   *
   * @return array
   *   An associative array of values, compatible with time slot.
   */
  public function getValues(): array {
    $values = [];
    foreach ($this as $key => $property) {
      $values[$key] = $property;
    }
    // Add values to return the season header as time slot.
    // From and To are Unix timestamps.
    // Solution: assign it the special day number 9 + Season ID.
    $values += [
      'day' => $this->id + OfficeHoursDateHelper::SEASON_DAY_MIN,
      'all_day' => FALSE,
      'starthours' => $this->from,
      'endhours' => $this->to,
      'comment' => $this->name,
    ];

    return $values;
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::setValue().
   *
   * @param array|null $values
   *   An array of property values.
   * @param bool $notify
   *   (optional) Whether to notify the parent object of the change. Defaults to
   *   TRUE. If a property is updated from a parent object, set it to FALSE to
   *   avoid being notified again.
   */
  public function setValue($values, $notify = TRUE) {
    $this->id = $values['id'] ?? $this->id;
    $this->name = $values['name'] ?? $this->name;
    $this->from = $values['from'] ?? $this->from;
    $this->to = $values['to'] ?? $this->to;
    // Values from OfficeHoursItem.
    $this->name = $values['comment'] ?? $this->name;
    $this->from = $values['starthours'] ?? $this->from;
    $this->to = $values['endhours'] ?? $this->to;

    // When Form is displayed the first time, date is an integer.
    // When 'Add exception' is pressed, date is a string "yyyy-mm-dd".
    if (!is_numeric($this->from)) {
      $this->from = strtotime($this->from);
    }
    if (!is_numeric($this->to)) {
      $this->to = strtotime($this->to);
    }
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
    return (($this->name == OfficeHoursSeason::SEASON_DEFAULT_NAME) && !$this->from);
  }

  /**
   * Returns if a season is in a given date range.
   *
   * @param int $from
   *   The start date of the date range.
   * @param int $to
   *   The duration (1..999) or end date (timestamp).
   *
   * @return bool
   *   TRUE if the given time period is in range, else FALSE.
   */
  public function isInRange(int $from, int $to): bool {
    $is_in_range = TRUE;

    if ($from == 0 && $to == 0) {
      return TRUE;
    }

    // Change start date + duration to start date + end date.
    if ($from > OfficeHoursDateHelper::SEASON_DAY_MIN) {
      if ($to == 0) {
        // A start_date with a horizon 0 is never in range.
        return FALSE;
      }
      if ($to < OfficeHoursDateHelper::SEASON_DAY_MAX) {
        // Convert duration to end date.
        $to = strtotime("+$to day", $from);
      }
    }

    $season = $this;
    if ($season->id()) {
      $minDate = $season->getFromDate();
      $maxDate = strtotime("+1 day midnight", $season->getToDate());
      // Season days. Weekdays are always in range.
      $is_in_range = ($minDate <= $to && $maxDate >= $from);
    }

    return $is_in_range;
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
   * Returns the translated Season name.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The name.
   */
  public function label() {
    // @todo Translate?
    // But if so, avoidLogicException Ajax error, by adding:
    // use DependencySerializationTrait;
    // use StringTranslationTrait;
    // Test by:
    // Enable locale module 'User interface translation.
    // Enable both exceptions and season in widget.
    // Edit with non-english page /nl/node/8/edit .
    // Click 'Add exception' button.
    // Check if an empty exception is added.
    // @see https://www.drupal.org/project/office_hours/issues/3399054
    // return $this->name;
    // return t($this->name);
    // return $this->t($this->name);
    return $this->name;
  }

  /**
   * Returns the untranslated Season name.
   *
   * @return string
   *   The Season name.
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
    return $this->formatDate($pattern, $day);
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
    return $this->formatDate($pattern, $day);
  }

  /**
   * Returns the translated label of a Weekday/Exception day, e.g., 'tuesday'.
   *
   * @param string $pattern
   *   The day/date formatting pattern.
   * @param int $day
   *   A day number or UNIX timestamp.
   *
   * @return string
   *   The formatted day label, e.g., 'tuesday'.
   */
  private function formatDate(string $pattern, int $day) : string {
    if (!OfficeHoursDateHelper::isValidDate($day)) {
      // No usage for season 0 or normal weekdays.
      return '';
    }
    if (!$pattern) {
      return $day;
    }
    $label = OfficeHoursDateHelper::format($day, $pattern);
    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(OfficeHoursSeason $a, OfficeHoursSeason $b) {
    // Sort the entities using the entity class's sort() method.
    $a_date = $a->getFromDate();
    $b_date = $b->getFromDate();
    if ($a_date < $b_date) {
      return -1;
    }
    if ($a_date > $b_date) {
      return +1;
    }
    return 0;
  }

}
