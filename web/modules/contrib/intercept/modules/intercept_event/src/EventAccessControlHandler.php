<?php

namespace Drupal\intercept_event;

use Drupal\entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Event associated entities.
 *
 * @see \Drupal\intercept_event\Entity\EventRegistration.
 * @see \Drupal\intercept_event\Entity\EventAttendance.
 * @see \Drupal\intercept_event\Entity\EventRecurrence.
 */
class EventAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($entity->getEntityTypeId() == 'event_registration' && $operation == 'cancel') {
      // Create a new base class for this.
      // @see RoomReservationAccessControlHandler
      $result = AccessResult::allowedIfHasPermission($account, 'cancel event_registration entities');
      // Ensure that access is evaluated again when the entity changes.
      return $result->addCacheableDependency($entity);
    }
    $account = $this->prepareUser($account);
    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = parent::checkAccess($entity, $operation, $account);

    if ($result->isNeutral() && $this->hasReferencedUser($entity)) {
      $result = $this->checkEntityUserReferencedPermissions($entity, $operation, $account);
    }

    // Ensure that access is evaluated again when the entity changes.
    return $result->addCacheableDependency($entity);
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
    return $entity->hasField('field_user');
  }

}
