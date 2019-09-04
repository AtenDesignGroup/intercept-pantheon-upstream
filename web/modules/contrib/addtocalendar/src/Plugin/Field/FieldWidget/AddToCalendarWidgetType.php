<?php

namespace Drupal\addtocalendar\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'example_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "add_to_calendar_widget_type",
 *   label = @Translation("Add to calendar widget type"),
 *   field_types = {
 *     "add_to_calendar_field"
 *   }
 * )
 */
class AddToCalendarWidgetType extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'checkbox',
      '#default_value' => !empty($items[0]->value),
    ];
    $element['value']['#title'] = 'Show add to calendar widget';
    $element['value']['#title_display'] = 'after';
    return $element;
  }

}
