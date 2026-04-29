<?php

namespace Drupal\existing_values_autocomplete_widget\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'existing_autocomplete_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "existing_autocomplete_field_widget",
 *   label = @Translation("Autocomplete: existing values"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class ExistingAutocompleteFieldWidget extends StringTextfieldWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['suggestions_count' => 15] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['suggestions_count'] = [
      '#type' => 'number',
      '#title' => $this->t('How many autocomplete suggestions to show?'),
      '#default_value' => $this->getSetting('suggestions_count'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = $this->t('Suggestions count: @suggestions_count', [
      '@suggestions_count' => $this->getSetting('suggestions_count'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $entity_type_id = $this->fieldDefinition->getTargetEntityTypeId();
    $element['value'] += [
      '#autocomplete_route_name' => 'existing_values_autocomplete_widget.autocomplete',
      '#autocomplete_route_parameters' => [
        'entity_type_id' => $entity_type_id,
        // If the entity is non-bundleable or the field is a base field, Drupal
        // uses the entity type ID as bundle value:
        'bundle' => $items->getFieldDefinition()->getTargetBundle() ?? $entity_type_id,
        'field_name' => $items->getName(),
      ],
    ];
    return $element;
  }

}
