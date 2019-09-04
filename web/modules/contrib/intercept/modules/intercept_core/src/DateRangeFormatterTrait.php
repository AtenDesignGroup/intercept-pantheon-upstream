<?php

namespace Drupal\intercept_core;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;

trait DateRangeFormatterTrait {

  protected $startDateFormat = 'n/j/Y';

  protected $startTimeFormat = 'g:i A';

  protected $endTimeFormat = 'g:i A';

  /**
   * @param DateRangeItem $field_item
   *
   * @return TranslatableMarkup
   */
  public function getDateRange(DateRangeItem $field_item) {
    $values = $this->getDateRangeReplacements($field_item);
    return $this->formatDateRange($values);
  }

  /**
   * @param DateRangeItem $field_item
   *
   * @return string
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function getDateRangeReplacements(DateRangeItem $field_item) {
    if (!$field_item || !$field_item->get('value') || !$field_item->get('end_value')) {
      return '';
    }
    if ($from_date = $field_item->get('value')->getDateTime()) {
      $values['@date'] = $from_date->format($this->startDateFormat);
      $values['@time_start'] = $from_date->format($this->startTimeFormat);
    }
    if ($to_date = $field_item->get('end_value')->getDateTime()) {
      $values['@time_end'] = $to_date->format($this->endTimeFormat);
    }
    return $values;
  }

  protected function formatDateRange(array $values) {
    return $this->t('@date: @time_start - @time_end', $values);
  }

}
