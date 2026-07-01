<?php

namespace Drupal\jsonapi_resources_test\Resource;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Http\Exception\CacheableNotFoundHttpException;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi_resources\Resource\EntityResourceBase;
use Drupal\jsonapi_resources_test\Entity\Query\ColorSchemeQueryFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Route;

/**
 * Processes requests for the colors to use in a particular color scheme.
 *
 * @noinspection PhpUnused
 */
class ColorScheme extends EntityResourceBase implements ContainerInjectionInterface {

  /**
   * The factory for creating queries to fetch color schemes.
   *
   * @var \Drupal\jsonapi_resources_test\Entity\Query\ColorSchemeQueryFactoryInterface
   */
  protected ColorSchemeQueryFactoryInterface $colorSchemeQueryFactory;

  /**
   * {@inheritdoc}
   *
   * @return static
   */
  public static function create(ContainerInterface $container): ColorScheme {
    return new static(
      $container->get('jsonapi_resources_test.color_query_factory')
    );
  }

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\jsonapi_resources_test\Entity\Query\ColorSchemeQueryFactoryInterface $color_scheme_query_factory
   *   The factory for creating queries to fetch color schemes.
   */
  public function __construct(
    ColorSchemeQueryFactoryInterface $color_scheme_query_factory,
  ) {
    $this->colorSchemeQueryFactory = $color_scheme_query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteResourceTypes(
    Route $route,
    string $route_name,
  ): array {
    return [ColorSchemeResourceObject::getResourceTypeDefinition()];
  }

  /**
   * Process the resource request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   * @param string $scheme_uuid
   *   The UUID of the color scheme to retrieve.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response to send back to the client.
   */
  public function process(
    Request $request,
    string $scheme_uuid,
  ): ResourceResponse {
    // No special caching.
    $cacheability = new CacheableMetadata();

    $scheme_uuid_lowercase = strtolower($scheme_uuid);

    $color_schemes =
      $this->colorSchemeQueryFactory
        ->createQueryForUuid($scheme_uuid_lowercase)
        ->execute();

    if (empty($color_schemes)) {
      throw new CacheableNotFoundHttpException(
        $cacheability,
        'Color scheme not defined: ' . $scheme_uuid
      );
    }

    $primary_data =
      ColorSchemeResourceObject::createFromColorSchemes(
        $color_schemes,
        $cacheability
      );

    $top_level_data = new ResourceObjectData($primary_data, 1);

    try {
      $response = $this->createJsonapiResponse($top_level_data, $request);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $ex) {
      throw new HttpException(
        Response::HTTP_INTERNAL_SERVER_ERROR,
        'Unable to load response plugin: ' . $ex->getMessage(),
        $ex
      );
    }

    if ($response instanceof CacheableResponseInterface) {
      $response->addCacheableDependency($cacheability);
    }

    return $response;
  }

}
