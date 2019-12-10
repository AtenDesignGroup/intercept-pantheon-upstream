<?php

namespace Drupal\intercept_event;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\EntityPermissionProvider;

/**
 * Permissions provider for Events.
 */
class EventPermissionProvider extends EntityPermissionProvider {

  /**
   * {@inheritdoc}
   */
  public function buildPermissions(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $plural_label = $entity_type->getPluralLabel();

    $permissions = parent::buildPermissions($entity_type);
    $permissions["view referenced user {$entity_type_id}"] = [
      'title' => $this->t("View referenced user's @type", ['@type' => $plural_label]),
    ];
    $permissions["update referenced user {$entity_type_id}"] = [
      'title' => $this->t("Update referenced user's @type", ['@type' => $plural_label]),
    ];
    if ($entity_type_id == 'event_registration') {
      $permissions["cancel {$entity_type_id} entities"] = [
        'title' => $this->t('Cancel @type', [
          '@type' => $plural_label,
        ]),
      ];
    }

    return $this->processPermissions($permissions, $entity_type);
  }

}
