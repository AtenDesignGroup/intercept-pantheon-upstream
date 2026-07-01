<?php

namespace Drupal\jsonapi_resources;

use Drupal\jsonapi\IncludeResolver as InnerIncludeResolver;
use Drupal\jsonapi\JsonApiResource\Data;
use Drupal\jsonapi\JsonApiResource\IncludedData;
use Drupal\jsonapi\JsonApiResource\LabelOnlyResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi_resources\Resource\ResourceObjectRelationship;

/**
 * Decorator around the JSON:API include resolver, to handle custom resources.
 */
class IncludeResolver extends InnerIncludeResolver {

  /**
   * The include resolver service we are decorating.
   *
   * @var \Drupal\jsonapi\IncludeResolver
   */
  protected InnerIncludeResolver $inner;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\jsonapi\IncludeResolver $inner
   *   The include resolver service we are decorating.
   *
   * @noinspection PhpMissingParentConstructorInspection
   */
  public function __construct(InnerIncludeResolver $inner) {
    $this->inner = $inner;
  }

  /**
   * {@inheritdoc}
   */
  protected function resolveIncludeTree(
    array $include_tree,
    Data $data,
    ?Data $includes = NULL,
  ) {
    // Resolve "normal" includes first.
    $includes =
      $this->inner->resolveIncludeTree($include_tree, $data, $includes);

    foreach ($include_tree as $field_name => $children) {
      $referenced_resources = [];

      foreach ($data as $resource_object) {
        if (!($resource_object instanceof ResourceObject) ||
            ($resource_object instanceof LabelOnlyResourceObject)) {
          continue;
        }

        $public_field_name =
          $resource_object->getResourceType()->getPublicName($field_name);

        if (!$resource_object->hasField($public_field_name)) {
          continue;
        }

        $value = $resource_object->getField($public_field_name);

        if (!$value instanceof ResourceObjectRelationship) {
          continue;
        }

        $referenced_resources = array_merge($referenced_resources, $value->getReferencedResources());
      }

      if (count($referenced_resources) > 0) {
        $targeted_collection = new IncludedData($referenced_resources);

        // Now, recurse on includes from custom relationships.
        $includes =
          $this->resolveIncludeTree(
            $children,
            $targeted_collection,
            IncludedData::merge($includes, $targeted_collection)
          );
      }
    }

    return $includes;
  }

}
