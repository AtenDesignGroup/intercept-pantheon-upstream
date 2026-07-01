<?php

namespace Drupal\jsonapi_resources_test\Resource;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi_resources\Resource\EntityQueryResourceBase;
use Drupal\jsonapi_resources_test\Entity\Query\ColorSchemeQueryFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Route;

/**
 * Processes requests for a listing of all color schemes.
 *
 * @noinspection PhpUnused
 */
class ColorSchemesList extends EntityQueryResourceBase implements ContainerInjectionInterface {

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
  public static function create(ContainerInterface $container): ColorSchemesList {
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
   * Process the resource request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   */
  public function process(Request $request): ResourceResponse {
    $cacheability =
      (new CacheableMetadata())->addCacheContexts([
        'url.query_args:page',
      ]);

    // @todo Add parsing of filter arguments. The query interface supports it
    //   but JSON:API Resources doesn't show how to apply filters to an entity
    //   query.
    $query = $this->colorSchemeQueryFactory->createQuery();

    $paginator = $this->getPaginatorForRequest($request);
    $paginator->applyToQuery($query, $cacheability);

    $color_schemes = $query->execute();

    $resources =
      ColorSchemeResourceObject::createFromColorSchemes(
        $color_schemes,
        $cacheability
      );

    $top_level_data   = new ResourceObjectData($resources);
    $pagination_links = $paginator->getPaginationLinks($query, $cacheability);

    try {
      return $this->createJsonapiResponse(
        $top_level_data,
        $request,
        200,
        [],
        $pagination_links
      );
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $ex) {
      throw new HttpException(
        Response::HTTP_INTERNAL_SERVER_ERROR,
        'Failed to generate JSON:API response: ' . $ex->getMessage(),
        $ex
      );
    }
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

}
