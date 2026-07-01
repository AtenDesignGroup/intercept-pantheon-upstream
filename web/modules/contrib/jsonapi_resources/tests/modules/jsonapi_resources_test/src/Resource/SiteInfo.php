<?php

namespace Drupal\jsonapi_resources_test\Resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeAttribute;
use Drupal\jsonapi_resources\Resource\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Exposes simple configuration as a JSON:API resource.
 *
 * Resources are not limited to entities. Any data source — including
 * configuration objects — can back a resource. The pattern is always the
 * same: build a ResourceObject with a ResourceType, wrap it in
 * ResourceObjectData, and let createJsonapiResponse() normalize it. The only
 * configuration-specific detail is cacheability: add the config object as a
 * cacheable dependency so the response is invalidated by the
 * "config:system.site" cache tag when the settings change.
 *
 * @internal
 */
final class SiteInfo extends ResourceBase implements ContainerInjectionInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a new SiteInfo object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): SiteInfo {
    return new static($container->get('config.factory'));
  }

  /**
   * Process the resource request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types
   *   The route resource types.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   */
  public function process(Request $request, array $resource_types): ResourceResponse {
    $config = $this->configFactory->get('system.site');

    // Inherit the config object's cacheability. This adds the
    // "config:system.site" cache tag, so the response is invalidated whenever
    // the site information settings are saved.
    $cacheability = new CacheableMetadata();
    $cacheability->addCacheableDependency($config);

    $resource_type = reset($resource_types);
    $primary_data = new ResourceObject(
      $cacheability,
      $resource_type,
      'system.site',
      NULL,
      [
        'name' => $config->get('name'),
        'slogan' => $config->get('slogan'),
        'mail' => $config->get('mail'),
      ],
      new LinkCollection([])
    );

    $top_level_data = new ResourceObjectData([$primary_data], 1);
    $response = $this->createJsonapiResponse($top_level_data, $request);

    if ($response instanceof CacheableResponseInterface) {
      $response->addCacheableDependency($cacheability);
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteResourceTypes(Route $route, string $route_name): array {
    $fields = [
      'name' => new ResourceTypeAttribute('name'),
      'slogan' => new ResourceTypeAttribute('slogan'),
      'mail' => new ResourceTypeAttribute('mail'),
    ];

    // A non-entity, read-only resource type: not internal, locatable, not
    // mutable, not versionable. It has no relationships, so the relatable
    // resource types default to an empty set.
    $resource_type = new ResourceType('site_info', 'site_info', NULL, FALSE, TRUE, FALSE, FALSE, $fields);

    return [$resource_type];
  }

}
