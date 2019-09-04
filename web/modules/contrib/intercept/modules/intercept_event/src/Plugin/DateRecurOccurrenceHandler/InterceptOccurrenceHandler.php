<?php

namespace Drupal\intercept_event\Plugin\DateRecurOccurrenceHandler;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\date_recur\Plugin\DateRecurOccurrenceHandler\DefaultDateRecurOccurrenceHandler;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\intercept_event\DateRecurRRule;

/**
 * @DateRecurOccurrenceHandler(
 *  id = "intercept_occurrence_handler",
 *  label = @Translation("InterCEPT occurrence handler"),
 * )
 */
class InterceptOccurrenceHandler extends DefaultDateRecurOccurrenceHandler {
  public function init(DateRecurItem $item) {
    $this->item = $item;
    if (!empty($item->rrule)) {
      $this->rruleObject = new DateRecurRRule($item->rrule, $this->getItemStartDate($item), $this->getItemEndDate($item), $item->timezone);
      $this->isRecurring = TRUE;
    }
    else {
      $this->isRecurring = FALSE;
    }
    $this->tableName = $this->getOccurrenceTableName($this->item->getFieldDefinition());
  }

  private function getItemComputedValue($field_name, $item) {
    $datetime_type = $item->getFieldDefinition()->getSetting('datetime_type');
    $storage_format = $datetime_type === DateTimeItem::DATETIME_TYPE_DATE ? DateTimeItemInterface::DATE_STORAGE_FORMAT : DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    return DrupalDateTime::createFromFormat($storage_format, $item->{$field_name}, $item->timezone);
  }

  private function getItemStartDate($item) {
    return $this->getItemComputedValue('value', $item);
  }

  private function getItemEndDate($item) {
    return $this->getItemComputedValue('end_value', $item);
  }
}
