<?php

namespace Drupal\intercept_core;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\EntityPermissionProvider;
use Drupal\Component\Utility\Unicode;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides permissions information for Reservations.
 */
class ReservationPermissionsProvider extends EntityPermissionProvider {

  /**
   * {@inheritdoc}
   */
  public function buildPermissions(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $plural_label = $entity_type->getPluralLabel();

    $permissions = parent::buildPermissions($entity_type);

    foreach (['cancel', 'approve', 'decline', 'view', 'request'] as $action) {
      // View permissions are the same for both granularities.
      $permissions["{$action} {$entity_type_id}"] = [
        'title' => $this->t('@action @type', [
          '@action' => Unicode::ucwords($action),
          '@type' => $plural_label,
        ]),
      ];
    }
    foreach (['cancel', 'update', 'view'] as $action) {
      $permissions["{$action} referenced user {$entity_type_id}"] = [
        'title' => $this->t("@action referenced user's @type", [
          '@action' => Unicode::ucwords($action),
          '@type' => $plural_label,
        ]),
      ];
    }

    return $this->processPermissions($permissions, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityTypePermissions(EntityTypeInterface $entity_type) {
    $permissions = parent::buildEntityTypePermissions(($entity_type));
    $entity_type_id = $entity_type->id();
    $has_owner = $entity_type->entityClassImplements(EntityOwnerInterface::class);
    $plural_label = $entity_type->getPluralLabel();

    if ($has_owner) {
      $permissions["view own {$entity_type_id}"] = [
        'title' => $this->t('View own @type', [
          '@type' => $plural_label,
        ]),
      ];
    }
    return $permissions;
  }

}
