<?php

declare(strict_types=1);

namespace Drupal\jsonapi_views\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi\Routing\Routes as JsonApiRoutes;
use Drupal\jsonapi_views\Resource\ViewsResource;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines dynamic routes.
 *
 * Each Views view and display combination will result in
 * a jsonapi resource at: /{jsonapi_namespace}/views/{view_id}/{display_id}
 */
class Routes implements ContainerInjectionInterface {

  const RESOURCE_NAME = ViewsResource::class;

  const JSONAPI_RESOURCE_KEY = '_jsonapi_resource';

  const JSONAPI_RESOURCE_TYPES_KEY = '_jsonapi_resource_types';

  const VIEW_KEY = 'view';

  const DISPLAY_KEY = 'display';

  /**
   * {@inheritdoc}
   */
  public function __construct(
    /**
     * Resource type bundle repository.
     */
    protected ResourceTypeRepositoryInterface $resourceTypeRepository,
    /**
     * Entity type bundle info interface.
     */
    protected EntityTypeBundleInfoInterface $entityTypeBundleInfo,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jsonapi.resource_type.repository'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function routes(): RouteCollection {
    $jsonapi_views_routes = new RouteCollection();
    $base_path = '/%jsonapi%/views';
    $views = Views::getEnabledViews();
    $resource_by_entity_type = [];

    foreach ($views as $view) {
      $view_name = $view->id();

      $entity_type = $view->getExecutable()->getBaseEntityType();

      if (!$entity_type) {
        continue;
      }
      $entity_type = $entity_type->id();
      if (array_key_exists($entity_type, $resource_by_entity_type)) {
        $bundle_resource_types = $resource_by_entity_type[$entity_type];
      }
      else {
        $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
        $bundles = array_keys($bundle_info);
        // A bundle can exist without a matching resource type. For example,
        // jsonapi_extras can disable a bundle's JSON:API resource. get()
        // returns NULL for that bundle. Drop those bundles here. A dropped
        // bundle must not appear in _jsonapi_resource_types or become the
        // resource_type default.
        $bundle_resource_types = array_filter(array_map(fn(int|string $bundle) => $this->resourceTypeRepository->get($entity_type, (string) $bundle), $bundles));
        $resource_by_entity_type[$entity_type] = $bundle_resource_types;
      }

      if (empty($bundle_resource_types)) {
        continue;
      }
      $resource_types = array_map(fn(ResourceType $resource_type) => $resource_type->getTypeName(), $bundle_resource_types);

      // Create routes for each display.
      foreach ($view->get('display') as $display) {
        $display_id = $display['id'];

        $views_display_route = new Route(implode('/', [
          $base_path,
          $view_name,
          $display_id,
        ]));
        $defaults = [
          static::JSONAPI_RESOURCE_KEY => static::RESOURCE_NAME,
          static::JSONAPI_RESOURCE_TYPES_KEY => $resource_types,
          static::VIEW_KEY => $view->id(),
          static::DISPLAY_KEY => $display_id,
        ];
        // A view with exactly one bundle has one correct resource type.
        // Set it here. Tools like the OpenAPI module's JSON:API
        // discovery read this default to describe the route.
        // A view with several bundles has no single correct resource
        // type. Leave the default unset. Do not report a fake type.
        if (count($bundle_resource_types) === 1) {
          $defaults[JsonApiRoutes::RESOURCE_TYPE_KEY] = reset($bundle_resource_types);
        }
        $views_display_route->addDefaults($defaults);

        $jsonapi_views_routes->add(sprintf('jsonapi_views.%s.%s', $view_name, $display_id), $views_display_route);
      }
    }

    $jsonapi_views_routes->addRequirements(['_access' => 'TRUE']);
    return $jsonapi_views_routes;
  }

}
