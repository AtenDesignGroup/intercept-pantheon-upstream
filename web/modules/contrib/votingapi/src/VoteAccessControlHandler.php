<?php

declare(strict_types=1);

namespace Drupal\votingapi;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the vote entity type.
 *
 * @see \Drupal\votingapi\Entity\Vote
 */
class VoteAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete votes');

      case 'view':
        if ($account->hasPermission('view any vote')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission('view own vote') && $account->id() == $entity->getOwnerId()) {
          return AccessResult::allowed()->cachePerUser();
        }
        return parent::checkAccess($entity, $operation, $account);

      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL) {
    return AccessResult::allowed();
  }

}
