<?php

namespace Drupal\tally\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'tally' field type.
 *
 * @FieldType(
 *   id = "tally_reference",
 *   category = @Translation("Tally"),
 *   label = @Translation("Tally reference"),
 *   description = @Translation("Advanced count information"),
 *   default_widget = "tally_default",
 *   default_formatter = "tally_default",
 *   list_class = "\Drupal\tally\Field\TallyReferenceFieldItemList",
 * )
 */
class TallyReference extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['count'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Count'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'handler' => 'default',
      'handler_settings' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::fieldSettingsForm($form, $form_state);
    $form['handler']['handler_settings']['auto_create_bundle']['#access'] = FALSE;
    $form['handler']['handler_settings']['auto_create']['#access'] = FALSE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['count'] = [
      'type' => 'int',
      'not null' => FALSE,
    ];
    return $schema;
  }

}
