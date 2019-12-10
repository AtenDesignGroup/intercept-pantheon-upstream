<?php

namespace Drupal\intercept_core\Field\Computed;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a trait for traversing entity references.
 */
trait ItemTraverseTrait {

  /**
   * Computes the values for an item list.
   */
  protected function computeValue() {
    if (!$fields = $this->getSetting('target_fields')) {
      return FALSE;
    }
    if ($target_entity = $this->traverse($this->getEntity(), $fields)) {
      $this->setValue($target_entity->id());
    }
  }

  /**
   * Traverses a list of fields in an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @param array $fields
   *   The entity reference fields to traverse.
   */
  private function traverse(EntityInterface $entity, array $fields) {
    $field = array_shift($fields);
    if ($entity->hasField($field) && !empty($entity->get($field)->entity)) {
      return empty($fields) ? $entity->{$field}->entity : $this->traverse($entity->{$field}->entity, $fields);
    }
    return FALSE;
  }

}
