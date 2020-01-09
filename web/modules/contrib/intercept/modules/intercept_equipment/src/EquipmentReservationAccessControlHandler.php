<?php

namespace Drupal\intercept_equipment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\entity\EntityAccessControlHandler;

/**
 * Access controller for the Equipment reservation entity.
 *
 * @see \Drupal\intercept_equipment\Entity\EquipmentReservation.
 */
class EquipmentReservationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $account = $this->prepareUser($account);
    /** @var \Drupal\Core\Access\AccessResult $result */
    /** @var \Drupal\intercept_equipment\Entity\EquipmentReservationInterface $entity */
    $result = parent::checkAccess($entity, $operation, $account);
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          $result = AccessResult::allowedIfHasPermission($account, 'view unpublished equipment reservation entities');
        }
        $result = AccessResult::allowedIfHasPermission($account, 'view published equipment reservation entities');

      case 'update':
        $result = AccessResult::allowedIfHasPermission($account, 'edit equipment reservation entities');

      case 'delete':
        $result = AccessResult::allowedIfHasPermission($account, 'delete equipment reservation entities');
    }

    if ($result->isNeutral() && $this->hasReferencedUser($entity)) {
      return $this->checkEntityUserReferencedPermissions($entity, $operation, $account);
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add equipment reservation entities');
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
    /** @var \Drupal\intercept_equipment\Entity\EquipmentReservationInterface $entity */
    $return = AccessResult::neutral()->cachePerUser();
    if (empty($entity->get('field_user')->entity)) {
      return $return;
    }
    if (($account->id() == $entity->get('field_user')->entity->id())) {
      return AccessResult::allowedIfHasPermissions($account, [
        "$operation referenced user {$entity->getEntityTypeId()}",
      ]);
    }
    return $return;
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
    /** @var \Drupal\intercept_equipment\Entity\EquipmentReservationInterface $entity */
    return $entity->hasField('field_user');
  }

}
