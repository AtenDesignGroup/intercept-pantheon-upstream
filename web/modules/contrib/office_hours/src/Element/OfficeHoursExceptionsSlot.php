<?php

namespace Drupal\office_hours\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a one-line text field form element for Exception days.
 *
 * @FormElement("office_hours_exceptions_slot")
 */
class OfficeHoursExceptionsSlot extends OfficeHoursWeekSlot {

  /**
   * {@inheritdoc}
   */
  public static function processOfficeHoursSlot(&$element, FormStateInterface $form_state, &$complete_form) {

    // Update $element['#value'] with default data and prepare $element widget.
    parent::processOfficeHoursSlot($element, $form_state, $complete_form);

    // Facilitate Exception day specific things, such as changing date.
    $day = $element['#value']['day'];
    $day_delta = $element['#day_delta'];
    $value = $element['#value'];
    $label = parent::getLabel('l', $value, $day_delta);

    // Override the hidden (Week widget) or select (List widget)
    // first time slot 'day', setting a 'date' select element + day name.
    $element['day'] = [
      '#type' => $day_delta ? 'hidden' : 'date',
      // Add a label/header/title for accessibility (a11y) screen readers.
      '#title' => 'The exception day',
      '#title_display' => 'invisible',
      '#prefix' => $day_delta
        ? "<div class='office-hours-more-label'>$label</div>"
        : "<div class='office-hours-label'>$label</div>",
      // Format the numeric day number to Y-m-d format for the widget.
      '#default_value' => (is_numeric($day)) ? date('Y-m-d', $day) : '',
    ];

    // Add 'day_delta' to facilitate changing and closing Exception days.
    // @todo This adds a loose column to the widget. Fix it, avoiding colspan.
    $element['day_delta'] = [
      '#type' => 'value', // 'hidden',
      '#value' => $day_delta,
    ];

    return $element;
  }

}
