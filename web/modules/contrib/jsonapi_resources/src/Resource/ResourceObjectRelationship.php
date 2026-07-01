<?php

namespace Drupal\jsonapi_resources\Resource;

use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\Relationship;
use Drupal\jsonapi\JsonApiResource\RelationshipData;
use Drupal\jsonapi\JsonApiResource\ResourceIdentifier;
use Drupal\jsonapi\JsonApiResource\ResourceObject;

/**
 * JSON:API relationship of a custom resource that references other resources.
 */
class ResourceObjectRelationship extends Relationship {

  /**
   * The JSON:API resource objects that are referenced.
   *
   * @var \Drupal\jsonapi\JsonApiResource\ResourceObject[]
   */
  protected array $referencedResources;

  /**
   * Gets the JSON:API resource objects that are referenced.
   *
   * @return \Drupal\jsonapi\JsonApiResource\ResourceObject[]
   *   The referenced resource objects.
   */
  public function getReferencedResources(): array {
    return $this->referencedResources;
  }

  /**
   * Creates a new Relationship from an array of Resource Objects.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject $context
   *   The resource object that references the other resource objects.
   * @param string $field_name
   *   The public name of the relationship field.
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject[] $referenced_resources
   *   The resource objects that are referenced.
   * @param int $cardinality
   *   The number of Resource Objects the new relationship may reference.
   *   Defaults to -1, for unlimited.
   * @param \Drupal\jsonapi\JsonApiResource\LinkCollection|null $links
   *   (optional) Any extra links for the Relationship.
   * @param array $meta
   *   (optional) Any relationship metadata.
   *
   * @return static
   *   An instantiated relationship object.
   */
  public static function createFromResourceObjects(
    ResourceObject $context,
    string $field_name,
    array $referenced_resources,
    int $cardinality = -1,
    ?LinkCollection $links = NULL,
    array $meta = [],
  ): static {
    if ($links === NULL) {
      $links = new LinkCollection([]);
    }

    return new static(
      $field_name,
      $referenced_resources,
      $links,
      $meta,
      $context,
      $cardinality
    );
  }

  /**
   * Constructs a new instance.
   *
   * This constructor is protected by design. To create a new relationship, use
   * static::createFromResourceObjects().
   *
   * @param string $public_field_name
   *   The public field name of the relationship field.
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject[] $referenced_resources
   *   The JSON:API resource objects that are referenced.
   * @param \Drupal\jsonapi\JsonApiResource\LinkCollection $links
   *   Any links for the relationship.
   * @param array $meta
   *   Any relationship metadata.
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject $context
   *   The relationship's context resource object. Use the
   *   self::withContext() method to establish a context.
   * @param int $cardinality
   *   The number of Resource Objects that this relationship may reference.
   *   Defaults to -1, for unlimited.
   *
   * @see \Drupal\jsonapi_resources\Resource\ResourceObjectRelationship::createFromResourceObjects()
   */
  protected function __construct(
    string $public_field_name,
    array $referenced_resources,
    LinkCollection $links,
    array $meta,
    ResourceObject $context,
    int $cardinality = -1,
  ) {
    parent::__construct(
      $public_field_name,
      new RelationshipData(
        self::resourcesToIdentifiers($referenced_resources),
        $cardinality
      ),
      $links,
      $meta,
      $context
    );

    $this->referencedResources = $referenced_resources;
  }

  /**
   * Converts Resource Objects into JSON:API resource identifiers.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject[] $resources
   *   The resources for which resource identifiers are desired.
   *
   * @return \Drupal\jsonapi\JsonApiResource\ResourceIdentifier[]
   *   Resource identifiers for each resource.
   */
  protected static function resourcesToIdentifiers(array $resources): array {
    return array_map(
      fn(ResourceObject $resource) => new ResourceIdentifier(
        $resource->getResourceType(),
        $resource->getId()
      ),
      $resources
    );
  }

}
