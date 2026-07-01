<?php

namespace Drupal\jsonapi_resources_test\Resource;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;
use Drupal\jsonapi\JsonApiResource\Link;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeAttribute;
use Drupal\jsonapi\ResourceType\ResourceTypeRelationship;
use Drupal\jsonapi_resources\Resource\ResourceObjectRelationship;

/**
 * A JSON:API Resource Object to represent a color scheme palette.
 */
class ColorSchemeResourceObject extends ResourceObject {

  /**
   * Builds a JSON:API resource type definition for Color Scheme resources.
   *
   * The definition is statically cached.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceType
   *   The resource type definition for color schemes.
   */
  public static function getResourceTypeDefinition(): ResourceType {
    static $scheme_resource_type = NULL;

    if ($scheme_resource_type === NULL) {
      $attributes = [
        'name' => new ResourceTypeAttribute('name'),
      ];

      $relationships = [
        'background_color' => new ResourceTypeRelationship('background_color'),
        'text_color'       => new ResourceTypeRelationship('text_color'),
        'link_color'       => new ResourceTypeRelationship('link_color'),
        'accent_colors'    => new ResourceTypeRelationship('accent_colors', NULL, TRUE, FALSE),
      ];

      $scheme_resource_type =
        new ResourceType(
          'color_scheme',
          'color_scheme',
          NULL,
          FALSE,
          TRUE,
          TRUE,
          FALSE,
          array_merge($attributes, $relationships)
        );

      $color_resource_type = ColorResourceObject::getResourceTypeDefinition();

      $scheme_resource_type->setRelatableResourceTypes([
        'background_color' => [$color_resource_type],
        'text_color'       => [$color_resource_type],
        'link_color'       => [$color_resource_type],
        'accent_colors'    => [$color_resource_type],
      ]);
    }

    return $scheme_resource_type;
  }

  /**
   * Converts data on multiple color schemes into Color Scheme resources.
   *
   * @param array[] $color_schemes
   *   A two-dimensional associative array of color scheme metadata, in the
   *   format returned by
   *   \Drupal\jsonapi_resources_test\Entity\Query\ColorQuery. At the top level,
   *   each key is the UUID of the scheme and the value is an array of metadata
   *   about that scheme.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   *   How the resource objects should be cached.
   *
   * @return static[]
   *   JSON:API Resource Objects to represent all of the given color schemes.
   */
  public static function createFromColorSchemes(
    array $color_schemes,
    CacheableDependencyInterface $cacheability,
  ): array {
    $resources = [];

    foreach ($color_schemes as $uuid => $color_scheme) {
      $resources[] =
        static::createFromColorScheme($uuid, $color_scheme, $cacheability);
    }

    return $resources;
  }

  /**
   * Converts color scheme information into a Color Scheme JSON:API resource.
   *
   * @param string $uuid
   *   The Universally Unique Identifier (UUID) for the Color Scheme.
   * @param array $color_scheme
   *   An associative array of color scheme metadata.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   *   How the resource object should be cached.
   *
   * @return static
   *   A JSON:API Resource Object to represent the given color scheme.
   */
  public static function createFromColorScheme(
    string $uuid,
    array $color_scheme,
    CacheableDependencyInterface $cacheability,
  ): ColorSchemeResourceObject {
    $accent_color_resources =
      array_map(
        static::toColorResource(...),
        $color_scheme['accent_colors']
      );

    return new ColorSchemeResourceObject(
      $uuid,
      $color_scheme['name'],
      static::toColorResource($color_scheme['background_color']),
      static::toColorResource($color_scheme['text_color']),
      static::toColorResource($color_scheme['link_color']),
      $accent_color_resources,
      $cacheability
    );
  }

  /**
   * Converts a color information tuple into a JSON:API Color Resource object.
   *
   * @param array|null $color_info
   *   Either an array containing two elements (color name and color hex value);
   *   or, NULL to indicate that the color is not defined.
   *
   * @return \Drupal\jsonapi_resources_test\Resource\ColorResourceObject|null
   *   Either a color resource object representing the given color; or, NULL if
   *   NULL was given for $color_info.
   */
  protected static function toColorResource(
    ?array $color_info,
  ): ?ColorResourceObject {
    if ($color_info === NULL) {
      return NULL;
    }

    [$color_name, $color_hex] = $color_info;

    $cacheability =
      CacheableMetadata::createFromObject($color_info)
        ->setCacheMaxAge(Cache::PERMANENT);

    return new ColorResourceObject(
      $color_name,
      $color_hex,
      $cacheability
    );
  }

  /**
   * Constructs a new instance.
   *
   * @param string $uuid
   *   The Universally Unique Identifier for this color scheme.
   * @param string $name
   *   The human-friendly name of the color.
   * @param \Drupal\jsonapi_resources_test\Resource\ColorResourceObject|null $background_color
   *   The color used behind all text and graphics in the application, when
   *   using this color scheme. Can be NULL to omit a background color.
   * @param \Drupal\jsonapi_resources_test\Resource\ColorResourceObject $text_color
   *   The color used for all text in the application, when using this color
   *   scheme.
   * @param \Drupal\jsonapi_resources_test\Resource\ColorResourceObject $link_color
   *   The color used for all hyperlinks in the application, when using this
   *   color scheme.
   * @param \Drupal\jsonapi_resources_test\Resource\ColorResourceObject[] $accent_colors
   *   Optional colors used for graphics and design elements to complement the
   *   text and background colors.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   *   The cacheability of the resource object.
   * @param \Drupal\jsonapi\JsonApiResource\LinkCollection|null $links
   *   Optional links to include in the resource. Defaults to an empty links
   *   collection.
   */
  public function __construct(
    string $uuid,
    string $name,
    ?ColorResourceObject $background_color,
    ColorResourceObject $text_color,
    ColorResourceObject $link_color,
    array $accent_colors,
    CacheableDependencyInterface $cacheability,
    ?LinkCollection $links = NULL,
  ) {
    if ($links === NULL) {
      $links = new LinkCollection([]);
    }

    if (!$links->hasLinkWithKey('self')) {
      $self_link = Url::fromRoute(
        'jsonapi_resources_test.color_scheme.individual',
        ['scheme_uuid' => $uuid]
      );

      $links =
        $links->withLink(
          'self',
          new Link(new CacheableMetadata(), $self_link, 'self')
        );
    }

    $resource_type = static::getResourceTypeDefinition();

    // The relationships below are built with $this as their context before
    // parent::__construct() runs. This is safe: Relationship::__construct()
    // only stores the context reference. $this->getId() and
    // $this->getResourceType() are not read until normalization, by which
    // time the parent constructor has fully initialized $this. The field
    // array cannot be built after parent::__construct(); ResourceObject takes
    // its fields as a constructor argument.
    //
    // An absent background color is still a to-one relationship; it must be
    // exposed as an empty relationship object ({"data": null}) rather than a
    // bare NULL, which is not a valid JSON:API relationship.
    $background_color_relationship =
      ResourceObjectRelationship::createFromResourceObjects(
        $this,
        'background_color',
        $background_color === NULL ? [] : [$background_color],
        1
      );

    $fields = [
      'name'             => $name,
      'background_color' => $background_color_relationship,
      'text_color'       => ResourceObjectRelationship::createFromResourceObjects(
        $this,
        'text_color',
        [$text_color],
        1
      ),
      'link_color'       => ResourceObjectRelationship::createFromResourceObjects(
        $this,
        'link_color',
        [$link_color],
        1
      ),
      'accent_colors'    => ResourceObjectRelationship::createFromResourceObjects(
        $this,
        'accent_colors',
        $accent_colors
      ),
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
