<?php

namespace Drupal\tally\Field;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\TraversableTypedDataInterface;

class TallyReferenceFieldItemList extends EntityReferenceFieldItemList {

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    $default_value = parent::processDefaultValue($default_value, $entity, $definition);
    return $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    $default_value = parent::defaultValuesFormSubmit($element, $form, $form_state);
    return $default_value;
  }

  /**
   * Count all deltas and return total.
   *
   * @return int
   */
  public function getTotal() {
    $value = $this->getValue();
    $counts = array_column($value, 'count');
    return array_reduce($counts, function($carry, $item) {
      $carry += (int) $item;
      return $carry;
    });
  }
}
