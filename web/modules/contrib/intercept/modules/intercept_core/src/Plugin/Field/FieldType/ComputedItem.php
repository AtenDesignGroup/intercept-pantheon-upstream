<?php

namespace Drupal\intercept_core\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\TraversableTypedDataInterface;

/**
 * Defines the 'intercept_computed' entity field type.
 *
 * @FieldType(
 *   id = "intercept_computed",
 *   label = @Translation("InterCEPT Computed"),
 *   description = @Translation("A field type for computed InterCEPT fields."),
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
