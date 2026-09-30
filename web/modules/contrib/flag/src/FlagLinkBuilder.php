<?php

namespace Drupal\flag;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides a lazy builder for flag links.
 */
class FlagLinkBuilder implements FlagLinkBuilderInterface, TrustedCallbackInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FlagServiceInterface $flagService,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['build'];
  }

  /**
   * {@inheritdoc}
   */
  public function build($entity_type_id, $entity_id, $flag_id, $view_mode = 'default') {
    // Load the entity.
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
    if (!$entity) {
      return [];
    }

    // Load the flag.
    $flag = $this->flagService->getFlagById($flag_id);
    if (!$flag) {
      return [];
    }

    // Get the entity's bundle (content type).
    $entity_bundle = $entity->bundle();

    // Get the bundles (content types) that this flag applies to.
    $flaggable_bundles = $flag->getBundles();

    // If no content types are selected for the flag,
    // assume it applies to all content types.
    if (empty($flaggable_bundles) || in_array($entity_bundle, $flaggable_bundles, TRUE)) {
      // Generate the flag link if the flag applies to this content type.
      return $flag->getLinkTypePlugin()->getAsFlagLink($flag, $entity, $view_mode);
    }

    // If the flag does not apply, return an empty array.
    return [];
  }

}
