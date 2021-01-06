<?php

namespace Drupal\intercept_event;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Event Registration entities.
 *
 * @see \Drupal\intercept_event\Entity\EventRegistration.
 */
class EventRegistrationAccessControlHandler extends EntityAccessControlHandler {

  use EventEntityAccessTrait;

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'cancel') {
      $result = AccessResult::allowedIfHasPermission($account, 'cancel event_registration entities');
      // Ensure that access is evaluated again when the entity changes.
      return $result->addCacheableDependency($entity);
    }
    $account = $this->prepareUser($account);
    $result = parent::checkAccess($entity, $operation, $account);

    if ($result->isNeutral() && $this->hasReferencedUser($entity)) {
      $result = $this->checkEntityUserReferencedPermissions($entity, $operation, $account);
    }

    // Ensure that access is evaluated again when the entity changes.
    return $result->addCacheableDependency($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $result = parent::checkCreateAccess($account, $context, $entity_bundle);
    if ($result->isNeutral()) {
      $permissions = [
        'administer ' . $this->entityTypeId,
        'create ' . $this->entityTypeId,
      ];
      if ($entity_bundle) {
        $permissions[] = 'create ' . $entity_bundle . ' ' . $this->entityTypeId;
      }

      $result = AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
    }

    return $result;
  }

}
