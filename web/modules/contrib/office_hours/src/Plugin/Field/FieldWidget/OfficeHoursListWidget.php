<?php

namespace Drupal\office_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Plugin implementation of an office_hours widget.
 *
 * @FieldWidget(
 *   id = "office_hours_list",
 *   label = @Translation("Office hours (list)"),
 *   description = @Translation("A basic widget for weekdays."),
 *   field_types = {
 *     "office_hours",
 *   },
 *   multiple_values = FALSE,
 * )
 */
class OfficeHoursListWidget extends OfficeHoursWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $default_value = [];

    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem $item */
    $item = $items[$delta] ?? NULL;

    // On fieldSettings page admin/structure/types/manage/<TYPE>/fields/<FIELD>,
    // $delta may be 0 for an empty list, so $item does not exist.
    if (!$item) {
      return $default_value;
    }

    // @todo Enable List widget for Season, Exception days.
    if (!$item->isWeekDay()) {
      $this->addMessage($item);
      return $default_value;
    }

    $default_value = $item->getValue();
    $day_index = $delta;
    $element['value'] = [
      '#type' => 'office_hours_list',
      '#default_value' => $default_value,
      '#day_index' => $day_index,
      '#day_delta' => 0,
        // Wrap all of the select elements with a fieldset.
      '#theme_wrappers' => [
        'fieldset',
      ],
      '#attributes' => [
        'class' => [
          'container-inline',
        ],
      ],
    ] + $element['value'];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    $element = parent::afterBuild($element, $form_state);

    foreach (Element::children($element) as $key) {
      // Remove the 'drag-n-drop reordering' element.
      // $element[$key]['#cardinality_multiple'] = FALSE;
      // Remove the little draggable 'Weight for row n' box.
      // unset($element[$key]['_weight']);
      // Remove core's 'Remove' action button.
      unset($element[$key]['_actions']);
    }

    return $element;
  }

  /**
   * This function repairs the anomaly we mentioned before.
   *
   * Reformat the $values, before passing to database.
   *
   * @see formElement(),formMultipleElements()
   *
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Reformat the $values, before passing to database.
    foreach ($values as &$item) {
      $item = $item['value'];
    }
    $values = parent::massageFormValues($values, $form, $form_state);

    return $values;
  }

}
