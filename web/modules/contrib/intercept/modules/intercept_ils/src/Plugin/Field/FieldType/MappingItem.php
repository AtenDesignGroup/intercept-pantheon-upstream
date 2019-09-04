<?php

namespace Drupal\intercept_ils\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\TraversableTypedDataInterface;

/**
 * Defines the 'ils_mapping' entity field type.
 *
 * @FieldType(
 *   id = "ils_mapping",
 *   label = @Translation("ILS Mapping"),
 *   description = @Translation("An entity field containing a path alias and related data."),
 *   no_ui = TRUE,
 *   default_widget = "path",
 *   list_class = "\Drupal\intercept_ils\Plugin\Field\FieldType\MappingItemList",
 * )
 */
class MappingItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['id'] = DataDefinition::create('string')
      ->setLabel(t('External ILS ID'));
    $properties['data'] = MapDataDefinition::create()
      ->setLabel(t('Data map'))
      ->setDescription(t('The mapped object data.'));

    return $properties;
  }

  public function setValue($values, $notify = TRUE) {
    // TODO: Eventually make this a dynamic property definition.
    foreach ($values['data'] as $key => $value) {
      if ($key == 'data') {
        continue;
      }
      $values[$key] = $value;
    }
    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->value['data']) && empty($this->value['id']);
  }

}
