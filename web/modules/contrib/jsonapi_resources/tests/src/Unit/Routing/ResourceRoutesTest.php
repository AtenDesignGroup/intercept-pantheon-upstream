<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi_resources\Unit\Routing;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Tests\UnitTestCase;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi_resources\Exception\RouteDefinitionException;
use Drupal\jsonapi_resources\Unstable\Routing\ResourceRoutes;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the JSON:API resource route subscriber.
 *
 * @group jsonapi_resources
 * @coversDefaultClass \Drupal\jsonapi_resources\Unstable\Routing\ResourceRoutes
 */
final class ResourceRoutesTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Tests route decoration.
   */
  public function testDecoratedRouteCollection() {
    $route_collection = new RouteCollection();
    $route_collection->add('generic_route', new Route('/generic'));
    $route_defaults = [
      '_jsonapi_resource' => '\\Drupal\\jsonapi_resources_test\\Resource\\AuthorArticles',
      '_jsonapi_resource_types' => ['node--article'],
    ];
    $route_collection->add('jsonapi_resource_route', new Route('/%jsonapi%/resource', $route_defaults));
    $route_collection->add('jsonapi_resource_multi_method_route', new Route(
      '/%jsonapi%/resource',
      $route_defaults,
      [],
      [],
      '',
      [],
      ['POST', 'PATCH'],
    ));

    $route_rebuild_event = new RouteBuildEvent($route_collection);

    $resource_type_repository = $this->prophesize(ResourceTypeRepositoryInterface::class);
    $container = $this->prophesize(ContainerInterface::class);
    $container->has($route_defaults["_jsonapi_resource"])->willReturn(TRUE);
    $container->get($route_defaults["_jsonapi_resource"])->willReturn(new $route_defaults["_jsonapi_resource"]());
    $resource_routes = new ResourceRoutes($resource_type_repository->reveal(), ['basic_auth' => 'basic_auth'], '/custom-base-path', $container->reveal());
    $resource_routes->decorateJsonapiResourceRoutes($route_rebuild_event);

    $generic_route = $route_collection->get('generic_route');
    $this->assertSame('/generic', $generic_route->getPath());
    $this->assertNull($generic_route->getOption('_auth'));
    $this->assertNull($generic_route->getRequirement('_format'));

    $jsonapi_resource_route = $route_collection->get('jsonapi_resource_route');
    $this->assertSame('/custom-base-path/resource', $jsonapi_resource_route->getPath());
    $this->assertSame(['GET'], $jsonapi_resource_route->getMethods());
    $this->assertSame(['basic_auth'], $jsonapi_resource_route->getOption('_auth'));
    $this->assertEquals('api_json', $jsonapi_resource_route->getRequirement('_format'));

    $multi_method_route = $route_collection->get('jsonapi_resource_multi_method_route');
    $this->assertSame('/custom-base-path/resource', $multi_method_route->getPath());
    $this->assertSame(['POST', 'PATCH'], $multi_method_route->getMethods());
    $this->assertSame(['basic_auth'], $multi_method_route->getOption('_auth'));
    $this->assertEquals('api_json', $multi_method_route->getRequirement('_format'));
  }

  /**
   * Tests for an exception when the %jsonapi% base path placeholder is missing.
   */
  public function testMissingBasePathPlaceholder() {
    $route_collection = new RouteCollection();
    $route_defaults = [
      '_jsonapi_resource' => '\\Drupal\\jsonapi_resources_test\\Resource\\AuthorArticles',
      '_jsonapi_resource_types' => ['node--article'],
    ];
    $route_collection->add('jsonapi_resource_route', new Route('/missing-base-path', $route_defaults));

    $route_rebuild_event = new RouteBuildEvent($route_collection);

    $resource_type_repository = $this->prophesize(ResourceTypeRepositoryInterface::class);
    $container = $this->prophesize(ContainerInterface::class);
    $container->has($route_defaults["_jsonapi_resource"])->willReturn(TRUE);
    $container->get($route_defaults["_jsonapi_resource"])->willReturn(new $route_defaults["_jsonapi_resource"]());
    $resource_routes = new ResourceRoutes($resource_type_repository->reveal(), ['basic_auth' => 'basic_auth'], '/custom-base-path', $container->reveal());

    $this->expectExceptionObject(new RouteDefinitionException("The jsonapi_resource_route route definition's path, `/missing-base-path`, must begin with `/%jsonapi%` so that the JSON:API base path can be substituted in its place."));
    $resource_routes->decorateJsonapiResourceRoutes($route_rebuild_event);
  }

}
