<?php

namespace Drupal\intercept_core;

use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;

/**
 * Trait for formatting DateRangeItems.
 */
trait DateRangeFormatterTrait {

  /**
   * The start date format.
   *
   * @var string
   */
  protected $startDateFormat = 'n/j/Y';

  /**
   * The start time format.
   *
   * @var string
   */
  protected $startTimeFormat = 'g:i A';

  /**
   * The end time format.
   *
   * @var string
   */
  protected $endTimeFormat = 'g:i A';

  /**
   * Formats a DateRangeItem object to a string.
   *
   * @param \Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem $field_item
   *   The DateRangeItem object.
   * @param string|null $timezone
   *   The PHP TimeZone string.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The date range string.
   */
  public function getDateRange(DateRangeItem $field_item, $timezone = 'UTC') {
    $values = $this->getDateRangeReplacements($field_item, $timezone);
    return $this->formatDateRange($values);
  }

  /**
   * Prepares a list of date, start time, and end time for a DateRangeItem.
   *
   * @param \Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem $field_item
   *   The DateRangeItem object.
   * @param string|null $timezone
   *   The PHP TimeZone string.
   *
   * @return array|string
   *   An array representing the date, start time, and end time.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function getDateRangeReplacements(DateRangeItem $field_item, $timezone = 'UTC') {
    if (!$field_item || !$field_item->get('value') || !$field_item->get('end_value')) {
      return '';
    }
    $dateTimezone = new \DateTimeZone($timezone);
    if ($from_date = $field_item->get('value')->getDateTime()) {
      $from_date->setTimezone($dateTimezone);
      $values['@date'] = $from_date->format($this->startDateFormat);
      $values['@time_start'] = $from_date->format($this->startTimeFormat);
    }
    if ($to_date = $field_item->get('end_value')->getDateTime()) {
      $to_date->setTimezone($dateTimezone);
      $values['@time_end'] = $to_date->format($this->endTimeFormat);
    }
    return $values;
  }

  /**
   * Formats an array of date and times to a string.
   *
   * @param array $values
   *   An array representing the date, start time, and end time.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The date range string.
   */
  protected function formatDateRange(array $values) {
    return $this->t('@date: @time_start - @time_end', $values);
  }

}
