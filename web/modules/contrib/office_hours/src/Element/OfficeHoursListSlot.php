<?php

namespace Drupal\office_hours\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

/**
 * Provides a one-line text field form element for the List Widget.
 *
 * @FormElement("office_hours_list")
 */
class OfficeHoursListSlot extends OfficeHoursBaseSlot {

  /**
   * {@inheritdoc}
   */
  public static function processOfficeHoursSlot(&$element, FormStateInterface $form_state, &$complete_form) {
    parent::processOfficeHoursSlot($element, $form_state, $complete_form);

    // The valueCallback() has populated the #value array.
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
    $item = $element['#value'];
    $day = $item->day;

    // Add standardized labels to time slot element.
    $field_settings = $element['#field_settings'];
    $labels = OfficeHoursItem::getPropertyLabels('#prefix', $field_settings);

    $element['day'] = [
      '#type' => 'select',
      // Add a label/header/title for accessibility (a11y) screen readers.
      '#title' => t('Weekday'),
      '#title_display' => 'invisible',
      '#options' => OfficeHoursDateHelper::weekDays(FALSE),
      '#default_value' => $day,
    ];
    if (isset($element['all_day'])) {
      $element['all_day'] += $labels['all_day'];
    }
    // $element['starthours'] += $labels['from'];
    $element['endhours'] += $labels['to'];
    if (isset($element['comment'])) {
      // $element['comment'] += $labels['comment'];
    }

    return $element;
  }

}
