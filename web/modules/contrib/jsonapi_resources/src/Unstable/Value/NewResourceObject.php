<?php

declare(strict_types=1);

namespace Drupal\jsonapi_resources\Unstable\Value;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\ResourceType\ResourceType;

/**
 * Represents a resource object to be created.
 *
 * A new resource object does not require many of the arguments required for
 * construction a "regular" resource object. For example, when adding a resource
 * object, an ID is not required.
 *
 * @internal
 *   Do not use this class. It is for internal use and will be phased out when
 *   core support for similar behavior exists.
 */
final class NewResourceObject extends ResourceObject {

  /**
   * The metadata to normalize.
   *
   * @var array
   */
  private array $meta;

  /**
   * NewResourceObject constructor.
   *
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   *   The cacheability for the resource object.
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type of the resource object.
   * @param string $id
   *   The resource object's ID.
   * @param mixed|null $revision_id
   *   The resource object's version identifier. NULL, if the resource object is
   *   not versionable.
   * @param array $fields
   *   An array of the resource object's fields, keyed by public field name.
   * @param \Drupal\jsonapi\JsonApiResource\LinkCollection $links
   *   The links for the resource object.
   * @param array $meta
   *   (optional) The metadata to normalize.
   */
  public function __construct(CacheableDependencyInterface $cacheability, ResourceType $resource_type, $id, $revision_id, array $fields, LinkCollection $links, array $meta = []) {
    parent::__construct($cacheability, $resource_type, $id, $revision_id, $fields, $links);
    $this->meta = $meta;
  }

  /**
   * Gets the metadata.
   *
   * @return array
   *   The metadata.
   */
  public function getMeta(): array {
    return $this->meta;
  }

  /**
   * Creates a new resource object from a decoded JSON:API request's data.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The resource type of the resource object to be created.
   * @param array $primary_data
   *   The decoded request's primary data. This is *not* denormalized data,
   *   rather, it is the raw decoded JSON from the request body that has not yet
   *   been denormalized into in-memory objects.
   * @param array $meta
   *   (optional) The metadata to normalize.
   *
   * @return \Drupal\jsonapi_resources\Unstable\Value\NewResourceObject
   *   A new resource object.
   */
  public static function createFromPrimaryData(ResourceType $resource_type, array $primary_data, array $meta = []): NewResourceObject {
    $id = $primary_data['id'] ?? \Drupal::service('uuid')->generate();
    $fields = array_merge(
      $primary_data['attributes'] ?? [],
      $primary_data['relationships'] ?? []
    );
    return new self(new CacheableMetadata(), $resource_type, $id, NULL, $fields, new LinkCollection([]), $meta);
  }

}
