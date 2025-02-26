<?php

namespace Drupal\office_hours\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

/**
 * Provides a one-line text field form element for Exception days.
 *
 * @FormElement("office_hours_exceptions_slot")
 */
class OfficeHoursExceptionsSlot extends OfficeHoursBaseSlot {

  /**
   * {@inheritdoc}
   */
  public static function processOfficeHoursSlot(&$element, FormStateInterface $form_state, &$complete_form) {
    parent::processOfficeHoursSlot($element, $form_state, $complete_form);

    // The valueCallback() has populated the #value array.
    $value = $element['#value'];
    $value = is_object($value) ? $value->getValue() : $value;
    $day = $value['day'];
    $day_delta = $element['#day_delta'];
    // Add day_delta for label() or isEmpty() call.
    $value['day_delta'] = $day_delta;

    $pattern = 'l';
    $label = OfficeHoursItem::formatLabel($pattern, $value, $day_delta);

    // Override the hidden (Week widget) or select (List widget)
    // first time slot 'day', setting a 'date' select element + day name.
    $format = OfficeHoursDateHelper::DATE_STORAGE_FORMAT;
    $element['day'] = [
      '#type' => $day_delta ? 'hidden' : 'date',
      // Add a label/header/title for accessibility (a11y) screen readers.
      '#title' => t('The exception day'),
      '#title_display' => 'invisible',
      '#prefix' => $day_delta
        ? "<div class='office-hours-more-label'>$label</div>"
        : "<div class='office-hours-label'>$label</div>",
      '#default_value' => $day_delta
        // Add 'day_delta' to facilitate changing and closing Exception days.
        ? 'exception_day_delta'
        // Format the numeric day number to Y-m-d format for the widget.
        : (is_numeric($day) ? OfficeHoursDateHelper::format($day, $format) : ''),
      // Add wrapper attribute for improved (a11y) screen reader experience.
      '#wrapper_attributes' => ['header' => !$day_delta],
    ];

    return $element;
  }

}
