<?php

namespace Drupal\office_hours\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a one-line text field form element for the Week Widget.
 *
 * @FormElement("office_hours_slot")
 */
class OfficeHoursWeekSlot extends OfficeHoursBaseSlot {

  /**
   * {@inheritdoc}
   */
  public static function processOfficeHoursSlot(&$element, FormStateInterface $form_state, &$complete_form) {
    parent::processOfficeHoursSlot($element, $form_state, $complete_form);

    // The valueCallback() has populated the #value array.
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
    $item = $element['#value'];
    $day = $item->day;
    $day_delta = $element['#day_delta'];
    // Add day_delta for label() or isEmpty() call.
    $item->day_delta = $day_delta;
    $settings = ['day_format' => 'long'];
    $label = $item->label($settings);

    // Override (hide) the 'day' select-field, only showing the Weekday name.
    $element['day'] = [
      '#type' => 'hidden',
      // Add a label/header/title, also for accessibility (a11y) screen readers.
      '#title' => $label,
      // '#title_display' => 'invisible',.
      '#prefix' => $day_delta
        ? "<div class='office-hours-more-label'>$label</div>"
        : "<div class='office-hours-label'>$label</div>",
      '#default_value' => $day,
    ];

    return $element;
  }

}
