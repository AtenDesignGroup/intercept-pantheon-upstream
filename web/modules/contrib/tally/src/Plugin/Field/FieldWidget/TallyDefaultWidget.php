<?php

namespace Drupal\tally\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'default' widget.
 *
 * @FieldWidget(
 *   id = "tally_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "tally_reference"
 *   }
 * )
 */
class TallyDefaultWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'placeholder' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $placeholder = $this->getSetting('placeholder');
    // Copied from NumberWidget::settingsSummary but allows for a placeholder
    // of "0".
    if (isset($placeholder) && $placeholder != "") {
      $summary[] = $this->t('Placeholder: @placeholder', ['@placeholder' => $placeholder]);
    }
    else {
      $summary[] = $this->t('No placeholder');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $field_name = $this->fieldDefinition->getName();
    $parents = $form['#parents'];

    $options = $this->getOptions($items->getEntity());
    $count = $options ? count($options) - 1 : 0;
    $count_max = 15;
    // Override widget state in parent function to set item count.
    if (!static::getWidgetState($parents, $field_name, $form_state)) {
      $field_state = [
        'items_count' => min($count, $count_max),
        'array_parents' => [],
      ];
      static::setWidgetState($parents, $field_name, $form_state, $field_state);
    }
    $build = parent::form($items, $form, $form_state, $get_delta);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    if (!$item = $this->getFieldItemDataObject($items, $delta)) {
      return [];
    }
    $element['count'] = [
      '#type' => 'number',
      '#default_value' => $item->count,
      '#attributes' => [
        'class' => ['js-tally-input'],
      ],
    ];
    $placeholder = $this->getSetting('placeholder');
    if (isset($placeholder) && $placeholder != "") {
      $element['count']['#attributes'] = [
        'placeholder' => $placeholder,
      ];
    }

    $element['label'] = [
      '#type' => 'item',
      '#markup' => $item->label,
    ];

    $element['target_id'] = [
      '#type' => 'hidden',
      '#value' => $item->id,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);
    unset($elements['add_more']);
    // Remove the 'drag-n-drop reordering' element.
    // @see template_preprocess_field_multiple_value_form()
    $elements['#cardinality_multiple'] = FALSE;
    $total = 0;
    $number_rows = 0;
    foreach (Element::children($elements) as $id) {
      // Remove the little 'Weight for row n' box.
      $elements[$id]['_weight']['#access'] = FALSE;
      $elements[$id]['count']['#min'] = 0;
      // Track total.
      $total = $total + $elements[$id]['count']['#default_value'];
      $number_rows++;
    }

    $elements['#type'] = 'table';
    $elements['#header'] = [];
    $elements['#caption'] = $elements['#title'];
    $elements['#theme_wrappers'][] = 'table';
    $elements['#attributes']['class'] = [
      'js-tally-container',
    ];
    $elements['#attached'] = [
      'library' => ['tally/widget'],
    ];
    $elements[$number_rows + 1] = [
      'count' => [
        '#default_value' => $total,
        '#type' => 'number',
        '#min' => 0,
        '#attributes' => [
          'disabled' => 'disabled',
          'class' => ['js-tally-total'],
        ],
      ],
      'label' => [
        '#type' => 'item',
        '#markup' => 'Total',
      ],
    ];
    return $elements;
  }

  /**
   * Helper function to gather data for a form row.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   An array of the field values.
   * @param int $delta
   *   The order of this item in the array of sub-elements (0, 1, 2, etc.).
   *
   * @return object|null
   *   An object of item values.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function getFieldItemDataObject(FieldItemListInterface $items, $delta) {
    if ($this->deltaExceedsOptionCount($items, $delta)) {
      return NULL;
    }
    $options = $this->getOptions($items->getEntity());
    $this->setArrayInternalPointer($options, $delta);
    $option_target_id = key($options);
    $option_label = current($options);
    return (object) [
      'id' => $option_target_id,
      'count' => $this->getItemCount($items, $option_target_id),
      'label' => $option_label,
    ];
  }

  private function deltaExceedsOptionCount(FieldItemListInterface $items, $delta) {
    $values = $this->getOptions($items->getEntity());
    return $delta >= count($values);
  }

  private function setArrayInternalPointer(array &$array, $pointer) {
    while ($pointer > 0) {
      $pointer--;
      next($array);
    }
  }

  /**
   * Fetch a count based on the target id, not delta.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   An array of the field values.
   * @param int $target_id
   *   The entity target ID.
   *
   * @return int|null
   *   The count integer at the specified position in this list, or NULL.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function getItemCount(FieldItemListInterface $items, $target_id) {
    $ids = array_column($items->getValue(), 'target_id');
    if (($id = array_search($target_id, $ids)) === FALSE) {
      return NULL;
    }
    $item = $items->get($id);
    return $item ? $item->getValue()['count'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$row) {
      $row['count'] = $row['count'] === "" ? NULL : $row['count'];
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    if ($element['#required'] && $element['#value'] == '_none') {
      $form_state->setError($element, new TranslatableMarkup('@name field is required.', ['@name' => $element['#title']]));
    }
    $items = [
      'target_id' => $element['target_id']['#value'],
      'count' => $element['count']['#value'],
    ];
    $form_state->setValueForElement($element, $items);
  }

}
