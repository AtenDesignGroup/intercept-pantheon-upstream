<?php

namespace Drupal\office_hours\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

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

    // Update $element['#value'] with default data and prepare $element widget.
    parent::processOfficeHoursSlot($element, $form_state, $complete_form);

    // Facilitate Weekday specific things, such as changing date.
    $day = $element['#value']['day'];
    $day_delta = $element['#day_delta'];
    $value = $element['#value'];
    $label = parent::getLabel('long', $value, $day_delta);

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

    $element['#attributes']['class'][] = "office-hours-day-$day";
    if ($day_delta == 0) {
      // This is the first slot of the day.
    }
    elseif (!OfficeHoursItem::isValueEmpty($value)) {
      // This is a following slot with contents.
      // Display the slot and display Add-link.
      $element['#attributes']['class'][] = 'office-hours-more';
    }
    else {
      // This is an empty following slot.
      // Hide the slot and Add-link, in case shown by js.
      $element['#attributes']['class'][] = 'office-hours-hide';
      $element['#attributes']['class'][] = 'office-hours-more';
    }

    return $element;
  }

}
