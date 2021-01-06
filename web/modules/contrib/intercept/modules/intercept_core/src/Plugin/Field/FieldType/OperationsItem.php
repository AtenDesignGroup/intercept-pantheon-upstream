<?php

namespace Drupal\intercept_core\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the 'operations' entity field type.
 *
 * @FieldType(
 *   id = "operations",
 *   label = @Translation("Operations"),
 *   description = @Translation("List of entity operations."),
 *   no_ui = TRUE,
 * )
 */
class OperationsItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return FALSE;
  }

}
