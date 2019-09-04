<?php

namespace Drupal\intercept_event;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Event Recurrence entity.
 *
 * @see \Drupal\intercept_event\Entity\EventRecurrence.
 */
class EventRecurrenceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\intercept_event\Entity\EventRecurrenceInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view event_recurrence');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'update event_recurrence');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete event_recurrence');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create event_recurrence');
  }

}
