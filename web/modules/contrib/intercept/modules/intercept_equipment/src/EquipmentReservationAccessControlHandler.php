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
    /** @var \Drupal\intercept_equipment\Entity\EquipmentReservationInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished equipment reservation entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published equipment reservation entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit equipment reservation entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete equipment reservation entities');
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

}
