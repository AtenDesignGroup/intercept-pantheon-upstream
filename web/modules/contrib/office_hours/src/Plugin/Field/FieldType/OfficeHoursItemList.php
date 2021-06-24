<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\office_hours\Element\OfficeHoursDatetime;
use Drupal\office_hours\OfficeHoursFormatterTrait;

/**
 * Represents an Office hours field.
 */
class OfficeHoursItemList extends FieldItemList implements OfficeHoursItemListInterface {

  use OfficeHoursFormatterTrait {
    getRows as getFieldRows;
  }

  /**
   * {@inheritdoc}
   */
  public function getRows(array $settings, array $field_settings, $time = NULL) {
    return $this->getFieldRows($this->getValue(), $settings, $field_settings, $time);
  }

  /**
   * {@inheritdoc}
   */
  public function isOpen($time = NULL) {

    // Loop through all lines.
    // Detect the current line and the open/closed status.
    // Convert the day_number to (int) to get '0' for Sundays, not 'false'.
    $time = ($time === NULL) ? \Drupal::time()->getRequestTime() : $time;
    $today = (int) idate('w', $time); // Get day_number sun=0 - sat=6.
    $now = date('Hi', $time); // 'Hi' format, with leading zero (0900).
    $is_open = FALSE;
    foreach ($this->getValue() as $key => $item) {
      // Calculate start and end times.
      $day = (int) $item['day'];
      // 'Hi' format, with leading zero (0900).
      $start = OfficeHoursDatetime::get($item['starthours'], 'Hi');
      $end = OfficeHoursDatetime::get($item['endhours'], 'Hi');

      if ($day - $today == -1 || ($day - $today == 6)) {
        // We were open yesterday evening, check if we are still open.
        if ($start >= $end && $end > $now) {
          $is_open = TRUE;
        }
      }
      elseif ($day == $today) {
        if ($start <= $now) {
          // We were open today, check if we are still open.
          if (($start > $end) // We are open until after midnight.
            || ($start == $end && !is_null($start)) // We are open 24hrs per day.
            || (($start < $end) && ($end > $now)) // We are open, normal time slot.
          ) {
            $is_open = TRUE;
          }
        }
      }
    }
    return $is_open;
  }

}
