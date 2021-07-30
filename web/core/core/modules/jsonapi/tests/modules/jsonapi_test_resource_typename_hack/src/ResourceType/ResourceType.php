<?php

namespace Drupal\jsonapi_test_resource_typename_hack\ResourceType;

use Drupal\jsonapi\ResourceType\ResourceType as ResourceTypeBase;

/**
 * Custom resource type.
 */
class ResourceType extends ResourceTypeBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(...$arguments) {
    parent::__construct(...$arguments);
    // That's what JSON:API Extras allows to do.
    $this->typeName = str_replace('--', '==', $this->typeName);
  }

}
