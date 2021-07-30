<?php

namespace Drupal\jsonapi_test_resource_typename_hack\ResourceType;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository as ResourceTypeRepositoryBase;

/**
 * Provides a repository of JSON:API resource types.
 */
class ResourceTypeRepository extends ResourceTypeRepositoryBase {

  /**
   * {@inheritdoc}
   */
  protected function createResourceType(EntityTypeInterface $entity_type, $bundle) {
    return new ResourceType(
      $entity_type->id(),
      $bundle,
      $entity_type->getClass(),
      $entity_type->isInternal(),
      static::isLocatableResourceType($entity_type, $bundle),
      static::isMutableResourceType($entity_type, $bundle),
      static::isVersionableResourceType($entity_type),
      static::getFields($this->getAllFieldNames($entity_type, $bundle), $entity_type, $bundle)
    );
  }

}
