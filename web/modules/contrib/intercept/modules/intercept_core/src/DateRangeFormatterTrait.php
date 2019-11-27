<?php

namespace Drupal\intercept_core;

use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;

trait DateRangeFormatterTrait {

  protected $startDateFormat = 'n/j/Y';

  protected $startTimeFormat = 'g:i A';

  protected $endTimeFormat = 'g:i A';

  /**
   * @param \Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem $field_item
   *
   * @param string|null $timezone
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getDateRange(DateRangeItem $field_item, $timezone = 'UTC') {
    $values = $this->getDateRangeReplacements($field_item, $timezone);
    return $this->formatDateRange($values);
  }

  /**
   * @param \Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem $field_item
   *
   * @param string|null $timezone
   *
   * @return array|string
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

  protected function formatDateRange(array $values) {
    return $this->t('@date: @time_start - @time_end', $values);
  }

}
