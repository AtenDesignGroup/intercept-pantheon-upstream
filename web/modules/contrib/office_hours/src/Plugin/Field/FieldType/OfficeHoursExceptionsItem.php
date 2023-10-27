<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Plugin implementation of the 'office_hours' field type.
 *
 * @FieldType(
 *   id = "office_hours_exceptions",
 *   label = @Translation("Office hours exception"),
 *   list_class = "\Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList",
 *   no_ui = TRUE,
 * )
 */
class OfficeHoursExceptionsItem extends OfficeHoursItem {

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // @todo Add random Exception day value in past and in near future.
    $value = [];
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function formatTimeSlot(array $settings) {
    if ($this->day == OfficeHoursItem::EXCEPTION_DAY) {
      // Exceptions header does not have time slot.
      return '';
    }
    return parent::formatTimeSlot($settings);
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(array $settings) {
    $day = $this->day;
    if ($day === '' || $day === NULL) {
      // A new Exception slot.
      // @todo Value deteriorates in ExceptionsSlot::validate().
      $label = '';
    }
    elseif ($day == OfficeHoursItem::EXCEPTION_DAY) {
      $label = $settings['exceptions']['title'] ?? '';
    }
    else {
      $exceptions_day_format = $settings['exceptions']['date_format'] ?? NULL;
      $day_format = $settings['day_format'];
      $days_suffix = $settings['separator']['day_hours'];
      $pattern = $exceptions_day_format ? $exceptions_day_format : $day_format;

      if ($pattern == 'l') {
        // Convert date into weekday in widget.
        $label = \Drupal::service('date.formatter')->format($day, 'custom', $pattern);
      }
      else {
        $label = \Drupal::service('date.formatter')->format($day, $pattern);
        // Remove excessive time part.
        $label = str_replace(' - 00:00', '', $label);
      }
      $label .= $days_suffix;
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function isExceptionDay() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isInRange($from, $to) {
    if ($to < $from) {
      // @todo Error. Raise try/catch exception.
      return FALSE;
    }

    if ($to < 0) {
      // @todo Undefined result. Raise try/catch exception.
      return TRUE;
    }

    if ($to == 0) {
      // All exceptions are OK.
      return TRUE;
    }

    $yesterday = strtotime('yesterday midnight');
    $today = strtotime('today midnight');
    $day = $this->day;
    $time = $this->parent->getRequestTime();

    if (OfficeHoursDateHelper::isExceptionDay($to)) {
      // $from-$to are calendar dates.
      // 'Hi' format, with leading zero (0900).
      $now = (int) OfficeHoursDateHelper::format($time, 'Hi');

      if ($day == $yesterday) {
        // We were open yesterday evening, check if we are still open.
        $slot = $this->getValue();
        $day = $slot['day'];
        $start = (int) $slot['starthours'];
        $end = (int) $slot['endhours'];
        if ($start >= $end && $end > $now) {
          return TRUE;
        }
        return FALSE;
      }
      elseif ($day < $yesterday) {
        return FALSE;
      } else {
        // @todo Undefined result. Raise try/catch exception.
        // There is no use case (yet) fopr Dates in the future.
        return TRUE;
      }
    }
    else {
      // $from-$to is a range, e.g., 0..7 days.
      // Time slots from yesterday with endhours after midnight are included.
      $minTime = $today + ($from - 1) * 24 * 60 * 60;
      $maxTime = $today + $to * 24 * 60 * 60;
      if ($day >= $minTime && $day <= $maxTime) {
        return TRUE;
      }
      return FALSE;
    }

  }

}
