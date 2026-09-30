<?php

namespace Drupal\flag\Plugin\Flag;

use Drupal\flag\FlagInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a flag type for comments.
 *
 * @FlagType(
 *   id = "entity:comment",
 *   title = @Translation("Comment"),
 *   entity_type = "comment",
 *   provider = "comment"
 * )
 */
class CommentFlagType extends EntityFlagType {

  /**
   * {@inheritdoc}
   */
  protected function getExtraPermissionsOptions() {
    $options = parent::getExtraPermissionsOptions();
    $options['parent_owner'] = $this->t("Permissions based on ownership of a comment's parent entity.");
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function actionPermissions(FlagInterface $flag) {
    $permissions = parent::actionPermissions($flag);

    // Define additional permissions.
    if ($this->hasExtraPermission('parent_owner')) {
      $permissions += $this->getExtraPermissionsParentOwner($flag, $this->configuration['extra_permissions']);
    }

    return $permissions;
  }

  /**
   * Defines permission for 'parent_owner' set of additional action permissions.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag object.
   * @param array $extra_permissions
   *   Array of extra permissions, if set.
   *
   * @return array
   *   An array of permissions.
   */
  protected function getExtraPermissionsParentOwner(FlagInterface $flag, array $extra_permissions) {
    $permissions = [];
    if (!empty($extra_permissions)) {
      foreach ($extra_permissions as $option) {
        switch ($option) {
          // The 'owner' case is handled by the parent method.
          case 'parent_owner':
            // Define additional permissions.
            $permissions['flag ' . $flag->id() . ' comments on own parent entities'] = [
              'title' => $this->t('Flag %flag_title comments on own parent entities', [
                '%flag_title' => $flag->label(),
              ]),
            ];

            $permissions['unflag ' . $flag->id() . ' comments on own parent entities'] = [
              'title' => $this->t('Unflag %flag_title on own parent entities', [
                '%flag_title' => $flag->label(),
              ]),
            ];

            $permissions['flag ' . $flag->id() . ' comments on other parent entities'] = [
              'title' => $this->t("Flag %flag_title on others' parent entities", [
                '%flag_title' => $flag->label(),
              ]),
            ];

            $permissions['unflag ' . $flag->id() . ' comments on other parent entities'] = [
              'title' => $this->t("Unflag %flag_title on others' parent entities", [
                '%flag_title' => $flag->label(),
              ]),
            ];
            break;
        }
      }
    }

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function actionAccess($action, FlagInterface $flag, AccountInterface $account, ?EntityInterface $flaggable = NULL) {
    $access = $this->parentActionAccess($action, $flag, $account, $flaggable);

    if ($this->hasExtraPermission('parent_owner')) {
      // Own items.
      $permission = $action . ' ' . $flag->id() . ' comments on own parent entities';
      $own_parent_permission_access = AccessResult::allowedIfHasPermission($account, $permission)
        ->addCacheContexts(['user']);
      /** @var \Drupal\comment\CommentInterface $flaggable */
      /** @var \Drupal\user\EntityOwnerInterface $parent_entity */
      $parent_entity = $flaggable->getCommentedEntity();
      $account_match_access = AccessResult::allowedIf($account->id() == $parent_entity->getOwnerId());
      $own_access = $own_parent_permission_access->andIf($account_match_access);
      $access = $access->orIf($own_access);

      // Others' items.
      $permission = $action . ' ' . $flag->id() . ' comments on other parent entities';
      $other_parent_permission_access = AccessResult::allowedIfHasPermission($account, $permission)
        ->addCacheContexts(['user']);
      $account_mismatch_access = AccessResult::allowedIf($account->id() != $parent_entity->getOwnerId());
      $others_access = $other_parent_permission_access->andIf($account_mismatch_access);
      $access = $access->orIf($others_access);
    }

    return $access;
  }

  /**
   * Get parent action access.
   *
   * @param string $action
   *   The action for which to check permissions, either 'flag' or 'unflag'.
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   An AccountInterface object.
   * @param \Drupal\Core\Entity\EntityInterface|null $flaggable
   *   (optional) The flaggable entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An AccessResult object.
   */
  protected function parentActionAccess(string $action, FlagInterface $flag, AccountInterface $account, ?EntityInterface $flaggable) {
    return parent::actionAccess($action, $flag, $account, $flaggable);
  }

}
