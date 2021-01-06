<?php

namespace Drupal\intercept_event;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides form functions for customer searching.
 */
trait EventEntityAccessTrait {

  /**
   * Checks the entity operation and bundle permissions, with owners.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check access.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'view label', 'update' or
   *   'delete'.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkEntityUserReferencedPermissions(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    if (empty($entity->get('field_user')->entity)) {
      return AccessResult::neutral()->cachePerUser();
    }
    if (($account->id() == $entity->get('field_user')->entity->id())) {
      return AccessResult::allowedIfHasPermissions($account, [
        "$operation referenced user {$entity->getEntityTypeId()}",
        "$operation referenced user {$entity->bundle()} {$entity->getEntityTypeId()}",
      ], 'OR');
    }
    return AccessResult::neutral()->cachePerUser();
  }

  /**
   * Whether the entity has an field_user field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check.
   *
   * @return bool
   *   Whether the entity has an field_user field.
   */
  protected function hasReferencedUser(EntityInterface $entity) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    return $entity->hasField('field_user');
  }

}
