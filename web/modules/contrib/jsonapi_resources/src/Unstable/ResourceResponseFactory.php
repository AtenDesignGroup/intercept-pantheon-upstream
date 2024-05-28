<?php

declare(strict_types=1);

namespace Drupal\jsonapi_resources\Unstable;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Http\Exception\CacheableBadRequestHttpException;
use Drupal\Core\Url;
use Drupal\jsonapi\CacheableResourceResponse;
use Drupal\jsonapi\IncludeResolver;
use Drupal\jsonapi\JsonApiResource\IncludedData;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\JsonApiResource\Link;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\NullIncludedData;
use Drupal\jsonapi\JsonApiResource\ResourceIdentifierInterface;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi\ResourceType\ResourceType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Creates JSON:API response objects.
 *
 * @internal
 *   Do not use this factory directly. Use
 *   \Drupal\jsonapi\Resource\ResourceBase::createJsonapiResponse() instead.
 */
final class ResourceResponseFactory {

  /**
   * The include resolver.
   *
   * @var \Drupal\jsonapi\IncludeResolver
   */
  protected $includeResolver;

  /**
   * ResourceResponseFactory constructor.
   *
   * @param \Drupal\jsonapi\IncludeResolver $include_resolver
   *   The include resolver.
   */
  public function __construct(IncludeResolver $include_resolver) {
    $this->includeResolver = $include_resolver;
  }

  /**
   * Builds a response with the appropriate wrapped document.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObjectData $data
   *   The data to wrap.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param int $response_code
   *   The response code.
   * @param array $headers
   *   An array of response headers.
   * @param \Drupal\jsonapi\JsonApiResource\LinkCollection $links
   *   The URLs to which to link. A 'self' link is added automatically.
   * @param array $meta
   *   (optional) The top-level metadata.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function create(ResourceObjectData $data, Request $request, $response_code = 200, array $headers = [], LinkCollection $links = NULL, array $meta = []) {
    $links = ($links ?: new LinkCollection([]));
    if (!$links->hasLinkWithKey('self')) {
      $self_link = new Link(new CacheableMetadata(), Url::fromUri($request->getUri()), 'self');
      $links = $links->withLink('self', $self_link);
    }

    $includes = $this->getIncludes($request, $data);

    $document = new JsonApiDocumentTopLevel($data, $includes, $links, $meta);

    if ($request->isMethodCacheable()) {
      $response = new CacheableResourceResponse($document, $response_code, $headers);
      // Make sure that different sparse fieldsets are cached differently.
      $cache_contexts[] = 'url.query_args:fields';
      // Make sure that different sets of includes are cached differently.
      $cache_contexts[] = 'url.query_args:include';
      $cacheability = (new CacheableMetadata())->addCacheContexts($cache_contexts);
      $response->addCacheableDependency($cacheability);
    }
    else {
      $response = new ResourceResponse($document, $response_code, $headers);
    }
    return $response;
  }

  /**
   * Gets includes for the given response data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObjectData|\Drupal\jsonapi\JsonApiResource\ResourceObject $data
   *   The response data from which to resolve includes.
   *
   * @return \Drupal\jsonapi\JsonApiResource\IncludedData
   *   A Data object to be included or a NullData object if the request does
   *   not
   *   specify any include paths.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getIncludes(Request $request, ResourceObjectData|ResourceObject $data): IncludedData {
    assert($data instanceof ResourceObject || $data instanceof ResourceObjectData);
    if (!$request->query->has('include')) {
      return new NullIncludedData();
    }
    $include_parameter = $request->query->get('include');
    if (empty($include_parameter)) {
      return new NullIncludedData();
    }
    if ($data instanceof ResourceObject) {
      return $this->includeResolver->resolve($data, $include_parameter);
    }

    /** @var \Drupal\jsonapi\ResourceType\ResourceType[] $route_resource_types */
    $route_resource_types = $request->attributes->get('resource_types');
    $relatable_resource_types = array_map(
      static fn (ResourceType $type) => array_keys($type->getRelatableResourceTypes()),
      $route_resource_types
    );

    // Group resource objects to optimize IncludeResolver::toIncludeTree.
    $resource_objects_by_type = [];
    foreach ($data as $resource_object) {
      assert($resource_object instanceof ResourceIdentifierInterface);
      $resource_objects_by_type[$resource_object->getTypeName()][] = $resource_object;
    }

    $include_paths = array_map('trim', explode(',', $include_parameter));
    $unresolved_include_paths = [];
    $included_data = [];
    foreach ($resource_objects_by_type as $resource_objects) {
      foreach ($include_paths as $include_path) {
        try {
          $included_data[] = $this->includeResolver->resolve(
            new ResourceObjectData($resource_objects),
            $include_path
          );
          $unresolved_include_paths[$include_path] = FALSE;
        }
        catch (\Exception) {
          if (!isset($unresolved_include_paths[$include_path])) {
            $unresolved_include_paths[$include_path] = TRUE;
          }
        }
      }
    }

    if (count(array_filter($unresolved_include_paths)) > 0) {
      // Throw an error if invalid include paths provided.
      // @see \Drupal\jsonapi\Context\FieldResolver::resolveInternalIncludePath().
      $message = sprintf(
        '%s are not valid relationship names.',
        implode(',', array_map(static fn (string $path) => "`$path`", array_keys($unresolved_include_paths)))
      );
      if (count($relatable_resource_types) > 0) {
        $message .= sprintf(' Possible values: %s', implode(', ', array_unique(array_merge(...$relatable_resource_types))));
      }
      throw new CacheableBadRequestHttpException(
        (new CacheableMetadata())->addCacheContexts(['url.query_args:include']),
        $message
      );
    }

    $included_data = array_reduce(
      $included_data,
      [IncludedData::class, 'merge'],
      new IncludedData([])
    );

    return IncludedData::deduplicate($included_data);
  }

}
