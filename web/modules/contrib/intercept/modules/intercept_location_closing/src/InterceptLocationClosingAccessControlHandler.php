<?php

namespace Drupal\intercept_location_closing;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Location Closing entity.
 *
 * @see \Drupal\intercept_location_closing\Entity\InterceptLocationClosing.
 */
class InterceptLocationClosingAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\intercept_location_closing\Entity\InterceptLocationClosingInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished location closing entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published location closing entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit location closing entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete location closing entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add location closing entities');
  }

}
