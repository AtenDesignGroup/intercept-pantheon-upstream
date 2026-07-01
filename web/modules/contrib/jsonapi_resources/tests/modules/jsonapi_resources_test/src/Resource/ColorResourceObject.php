<?php

namespace Drupal\jsonapi_resources_test\Resource;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeAttribute;

/**
 * A JSON:API Resource Object to represent a single color.
 */
class ColorResourceObject extends ResourceObject {

  /**
   * The prefix used for generating UUIDs from color values.
   *
   * This is just a quick and dirty way to get UUIDs from color values. A more
   * correct implementation would be to use v5 UUIDs generated from the color
   * strings (e.g., a hex string like "104773"), but that would require us to
   * add a library dependency like "ramsey/uuid" to this project.
   */
  protected const UUID_PREFIX = '2c9189c4-48a9-4f64-8e91-000000';

  /**
   * Builds a JSON:API resource type definition for Color resources.
   *
   * The definition is statically cached.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceType
   *   The resource type definition for colors.
   */
  public static function getResourceTypeDefinition(): ResourceType {
    static $resource_type = NULL;

    if ($resource_type === NULL) {
      $fields = [
        'name'      => new ResourceTypeAttribute('name'),
        'hex_value' => new ResourceTypeAttribute('hex_value'),
      ];

      $resource_type =
        new ResourceType(
          'color',
          'color',
          NULL,
          FALSE,
          TRUE,
          TRUE,
          FALSE,
          $fields
        );

      $resource_type->setRelatableResourceTypes([]);
    }

    return $resource_type;
  }

  /**
   * Gets the UUID for a given color value in hexadecimal.
   *
   * @param string $color_hex
   *   The six-digit hexadecimal string that describes the color.
   *
   * @return string
   *   The UUID for the given color value.
   */
  public static function uuidForColorValue(string $color_hex): string {
    $lowercase_color_hex = strtolower($color_hex);

    if (!preg_match('/\A[a-f0-9]{6}\z/', $lowercase_color_hex)) {
      throw new \InvalidArgumentException(
        'Colors must be expressed as six-digit, hexadecimal values.'
      );
    }

    return static::UUID_PREFIX . $lowercase_color_hex;
  }

  /**
   * Constructs a new instance.
   *
   * @param string $name
   *   The human-friendly name of the color.
   * @param string $color_hex
   *   The six-character hexadecimal value of the color.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   *   The cacheability of the resource object.
   * @param \Drupal\jsonapi\JsonApiResource\LinkCollection|null $links
   *   Optional links to include in the resource. Defaults to an empty links
   *   collection.
   */
  public function __construct(
    string $name,
    string $color_hex,
    CacheableDependencyInterface $cacheability,
    ?LinkCollection $links = NULL,
  ) {
    if ($links === NULL) {
      $links = new LinkCollection([]);
    }

    $resource_type = static::getResourceTypeDefinition();
    $uuid = static::uuidForColorValue($color_hex);

    $fields = [
      'name' => $name,
      'hex_value' => $color_hex,
    ];

    parent::__construct(
      $cacheability,
      $resource_type,
      $uuid,
      NULL,
      $fields,
      $links
    );
  }

}
