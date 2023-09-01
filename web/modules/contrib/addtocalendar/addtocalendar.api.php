<?php

/**
 * @file
 * Hooks and API provided by the "Add To Calendar" module.
 */

use Drupal\Core\Entity\EntityInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows to alter the field's data comes from an entity.
 *
 * @param $value
 * @param \Drupal\Core\Entity\EntityInterface $entity
 */
function hook_addtocalendar_field_alter(&$value, EntityInterface $entity) {
  // Here we have full access to $value.
  $value = 'This field was altered.';
}

/**
 * Allows to alter the field's data comes from an entity by field name.
 *
 * @param $value
 * @param \Drupal\Core\Entity\EntityInterface $entity
 */
function hook_addtocalendar_field_FIELD_NAME_alter(&$value, EntityInterface $entity) {
  // Here we have full access to $value.
  $value = 'This field was altered.';
}

/**
 * @} End of "addtogroup hooks".
 */
