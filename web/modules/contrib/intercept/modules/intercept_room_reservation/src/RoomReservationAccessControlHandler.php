<?php

namespace Drupal\intercept_room_reservation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\entity\EntityAccessControlHandler;

/**
 * Access controller for the Room reservation entity.
 *
 * @see \Drupal\intercept_room_reservation\Entity\RoomReservation.
 */
class RoomReservationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $account = $this->prepareUser($account);
    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = parent::checkAccess($entity, $operation, $account);

    switch ($operation) {
      case 'copy':
      case 'view':
      case 'update':
        if ($result->isNeutral() && $this->hasReferencedUser($entity)) {
          $result = $this->checkEntityUserReferencedPermissions($entity, $operation, $account);
        }
        break;

      case 'cancel':
        if ($result->isNeutral() && $this->hasReferencedUser($entity)) {
          $result = $this->checkEntityUserReferencedPermissions($entity, $operation, $account);
        }
        else {
          return AccessResult::allowedIfHasPermission($account, 'cancel room_reservation');
        }
        break;

      case 'approve':
        return AccessResult::allowedIfHasPermission($account, 'approve room_reservation');

      case 'deny':
        return AccessResult::allowedIfHasPermission($account, 'deny room_reservation');

      case 'archive':
        return AccessResult::allowedIfHasPermission($account, 'archive room_reservation');

      case 'request':
        return AccessResult::allowedIfHasPermission($account, 'request room_reservation');
    }

    return $result->addCacheableDependency($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create room_reservation');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkEntityOwnerPermissions(EntityInterface $entity, $operation, AccountInterface $account) {
    $result = parent::checkEntityOwnerPermissions($entity, $operation, $account);
    if ($operation == 'view' && $result->isNeutral() && $account->id() == $entity->getOwnerId()) {
      $permissions = [
        "view own {$entity->getEntityTypeId()}",
      ];
      $result = AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
    }
    return $result;
  }

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
    $return = AccessResult::neutral()->cachePerUser();
    if (empty($entity->get('field_user')->entity)) {
      return $return;
    }
    if (($account->id() == $entity->get('field_user')->entity->id())) {
      return AccessResult::allowedIfHasPermissions($account, [
        "$operation referenced user {$entity->getEntityTypeId()}",
        "$operation referenced user {$entity->bundle()} {$entity->getEntityTypeId()}",
      ], 'OR');
    }
    else {
      switch ($operation) {
        case 'cancel':
        case 'update':
        case 'approve':
        case 'deny':
        case 'archive':
          return AccessResult::allowedIfHasPermissions($account, [
            "update any {$entity->getEntityTypeId()}",
          ], 'OR');
      }
    }
    return $return;
  }

  /**
   * Check if entity has referenced user field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   Whether the entity has the field_user field.
   */
  protected function hasReferencedUser(EntityInterface $entity) {
    return $entity->hasField('field_user');
  }

}
