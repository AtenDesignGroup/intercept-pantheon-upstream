<?php

namespace Drupal\intercept_certification;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Certification entity.
 *
 * @see \Drupal\intercept_certification\Entity\Certification.
 */
class CertificationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\intercept_certification\Entity\CertificationInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished certification entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published certification entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit certification entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete certification entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add certification entities');
  }


}
