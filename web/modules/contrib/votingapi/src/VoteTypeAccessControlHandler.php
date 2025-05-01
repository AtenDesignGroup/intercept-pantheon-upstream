<?php

declare(strict_types=1);

namespace Drupal\votingapi;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the vote type entity type.
 *
 * @see \Drupal\votingapi\Entity\VoteType
 */
class VoteTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * @phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // phpcs:enable
    return parent::checkAccess($entity, $operation, $account);
  }

}
