<?php

namespace Drupal\intercept_event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Event Attendance entity.
 *
 * @see \Drupal\intercept_event\Entity\EventAttendance.
 */
class EventAttendanceAccessControlHandler extends EventAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\intercept_event\Entity\EventAttendanceInterface $entity */
    switch ($operation) {
      case 'scan':
        return AccessResult::allowedIfHasPermission($account, 'scan event_attendance');
    }

    // All other operations follow the parent.
    return parent::checkAccess($entity, $operation, $account);
  }

}
