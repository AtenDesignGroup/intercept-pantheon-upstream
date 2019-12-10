<?php

namespace Drupal\intercept_core\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;

/**
 * Defines the 'intercept_computed' entity field type.
 *
 * @FieldType(
 *   id = "intercept_computed",
 *   label = @Translation("Intercept Computed"),
 *   description = @Translation("A field type for computed Intercept fields."),
 *   no_ui = TRUE,
 *   list_class = "\Drupal\intercept_core\Plugin\Field\FieldType\ComputedItemList",
 * )
 */
class ComputedItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    if (empty($field_definition->getSetting('properties'))) {
      throw new \Exception('Properties setting must be set.');
    }
    $properties = $field_definition->getSetting('properties');
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

}
