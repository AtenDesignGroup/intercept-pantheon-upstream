<?php

namespace Drupal\intercept_bulk_room_reservation;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the bulk room reservation entity type.
 */
class BulkRoomReservationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view bulk room reservation');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit bulk room reservation', 'administer bulk room reservation'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete bulk room reservation', 'administer bulk room reservation'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create bulk room reservation', 'administer bulk room reservation'], 'OR');
  }

}
