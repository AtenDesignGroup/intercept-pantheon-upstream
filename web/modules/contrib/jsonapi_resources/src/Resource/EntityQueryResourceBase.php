<?php

declare(strict_types=1);

namespace Drupal\jsonapi_resources\Resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Http\Exception\CacheableBadRequestHttpException;
use Drupal\jsonapi\Access\TemporaryQueryGuard;
use Drupal\jsonapi\Context\FieldResolver;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\Query\Filter;
use Drupal\jsonapi\Query\Sort;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi_resources\Entity\Query\PaginatorInterface;
use Drupal\jsonapi_resources\Entity\Query\TotalCountAwareInterface;
use Drupal\jsonapi_resources\Unstable\Entity\Query\CacheabilityCapturingExecutor;
use Drupal\jsonapi_resources\Unstable\Entity\Query\Pagination\OffsetLimitPaginator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines basic functionality for an entity query-oriented JSON:API Resource.
 */
abstract class EntityQueryResourceBase extends EntityResourceBase {

  /**
   * The entity query executor utility.
   *
   * @var \Drupal\jsonapi_resources\Unstable\Entity\Query\CacheabilityCapturingExecutor
   */
  private $entityQueryExecutor;

  /**
   * The JSON:API field resolver.
   *
   * @var \Drupal\jsonapi\Context\FieldResolver
   */
  private $fieldResolver;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $fieldManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * Sets the cacheability capturing entity query executor.
   *
   * @param \Drupal\jsonapi_resources\Unstable\Entity\Query\CacheabilityCapturingExecutor $entity_query_executor
   *   The entity query executor utility.
   */
  public function setCacheabilityCapturingExecutor(CacheabilityCapturingExecutor $entity_query_executor) {
    $this->entityQueryExecutor = $entity_query_executor;
  }

  /**
   * Sets the JSON:API field resolver.
   *
   * @param \Drupal\jsonapi\Context\FieldResolver $field_resolver
   *   The JSON:API field resolver.
   */
  public function setFieldResolver(FieldResolver $field_resolver): void {
    $this->fieldResolver = $field_resolver;
  }

  /**
   * Sets the entity field manager.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The entity field manager.
   */
  public function setFieldManager(EntityFieldManagerInterface $field_manager): void {
    $this->fieldManager = $field_manager;
  }

  /**
   * Sets the module handler.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function setModuleHandler(ModuleHandlerInterface $module_handler): void {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Gets an entity query for the given entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID for the entity query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityQuery($entity_type_id) {
    return $this->entityTypeManager->getStorage($entity_type_id)->getQuery();
  }

  /**
   * Gets an entity query paginator for the current request.
   *
   * Currently, this will always returns an OffsetLimitPaginator, but it's
   * possible that it may return other paginator types in the future. Such as a
   * cursor-based paginator.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\jsonapi_resources\Entity\Query\PaginatorInterface
   *   A paginator for the request.
   */
  protected function getPaginatorForRequest(Request $request): PaginatorInterface {
    return OffsetLimitPaginator::create($request, $this->entityQueryExecutor);
  }

  /**
   * Builds `['count' => int]` meta for a collection response.
   *
   * Calling this method is the resource's opt-in to `meta.count`. The count
   * costs an extra database round-trip, so resources that do not need it
   * simply do not call this. Returns an empty array — and runs no count
   * query — when the active paginator does not implement
   * {@see TotalCountAwareInterface}.
   *
   * @param \Drupal\jsonapi_resources\Entity\Query\PaginatorInterface $paginator
   *   The paginator used for the request.
   * @param \Drupal\Core\Entity\Query\QueryInterface|\Drupal\Core\Database\Query\SelectInterface $executed_query
   *   The query the paginator was applied to. Already executed.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheable_metadata
   *   Cache metadata to capture from the count query.
   *
   * @return array
   *   `['count' => int]` when the paginator can count, an empty array
   *   otherwise. Pass the result straight to `createJsonapiResponse()`'s
   *   `$meta` argument.
   */
  protected function buildCountMeta(PaginatorInterface $paginator, QueryInterface|SelectInterface $executed_query, CacheableMetadata $cacheable_metadata): array {
    if (!$paginator instanceof TotalCountAwareInterface) {
      return [];
    }
    return ['count' => $paginator->getTotalCount($executed_query, $cacheable_metadata)];
  }

  /**
   * Finds entity resource object using an entity query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $entity_query
   *   The entity query object.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheable_metadata
   *   A CacheableMetadata object that will be used to capture any cacheability
   *   information generated while generating pagination links. The same object
   *   that is passed to this method should be added to the cacheability of the
   *   final response by the caller.
   * @param bool $load_latest_revisions
   *   (optional) Whether to load the latest revisions instead of the defaults.
   *   Defaults to FALSE.
   * @param bool $check_access
   *   (optional) Whether to run JSON:API's entity access check on each loaded
   *   entity. Defaults to TRUE. Passing FALSE serializes every loaded entity
   *   in full and is only safe when the entity query itself has already
   *   restricted results to entities the current user may view. See
   *   {@see EntityResourceBase::createCollectionDataFromEntities()}.
   *
   * @return \Drupal\jsonapi\JsonApiResource\ResourceObjectData
   *   The resource object data. If $check_access is TRUE, whether the user
   *   has access to each entity will be checked.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  protected function loadResourceObjectDataFromEntityQuery(QueryInterface $entity_query, CacheableMetadata $cacheable_metadata, $load_latest_revisions = FALSE, $check_access = TRUE): ResourceObjectData {
    $entity_type_id = $entity_query->getEntityTypeId();
    $results = $this->entityQueryExecutor->executeQueryAndCaptureCacheability($entity_query, $cacheable_metadata);
    return $this->loadResourceObjectsByEntityIds($entity_type_id, $results, $load_latest_revisions, $check_access);
  }

  /**
   * Finds entity resource objects using a raw database select query.
   *
   * Use this when the entity query API cannot express the query — for example
   * when joining tables that core's entity query does not expose. Pagination
   * still goes through the standard paginator: both QueryInterface and
   * SelectInterface support `range()` and `addMetaData()`.
   *
   * The select must return at least one column containing the target entity
   * IDs (e.g. `n.nid` for nodes); name that column in `$id_field`. Other
   * selected columns are ignored — the IDs are passed to the entity storage
   * loader, so the returned resource objects come from the standard entity
   * load + access-check pipeline.
   *
   * @param string $entity_type_id
   *   The entity type ID of the entities to load (e.g. `'node'`). The select
   *   query has no notion of an entity type, so this must be supplied.
   * @param \Drupal\Core\Database\Query\SelectInterface $select_query
   *   The database select query.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheable_metadata
   *   A CacheableMetadata object that will be used to capture any cacheability
   *   information generated by query execution. The same object passed to
   *   this method should be added to the cacheability of the final response.
   * @param string $id_field
   *   The name of the column in the select's result rows that holds the
   *   entity ID. Use the alias the column was selected under (`SELECT nid`
   *   → `'nid'`).
   * @param bool $check_access
   *   (optional) Whether to run JSON:API's entity access check on each loaded
   *   entity. Defaults to TRUE. Passing FALSE is only safe when the select
   *   itself has already restricted results to entities the current user may
   *   view.
   *
   * @return \Drupal\jsonapi\JsonApiResource\ResourceObjectData
   *   The resource object data.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  protected function loadResourceObjectDataFromSelectQuery(string $entity_type_id, SelectInterface $select_query, CacheableMetadata $cacheable_metadata, string $id_field, bool $check_access = TRUE): ResourceObjectData {
    $rows = $this->entityQueryExecutor->executeSelectAndCaptureCacheability($select_query, $cacheable_metadata);
    $ids = array_column($rows, $id_field);
    return $this->loadResourceObjectsByEntityIds($entity_type_id, $ids, FALSE, $check_access);
  }

  /**
   * Loads and access checks entities loaded by ID as JSON:API resource objects.
   *
   * @param string $entity_type_id
   *   The entity type ID of the entities to load.
   * @param int[] $ids
   *   An array of entity IDs, keyed by revision ID if the entity type is
   *   revisionable.
   * @param bool $load_latest_revisions
   *   (optional) Whether to load the latest revisions instead of the defaults.
   *   Defaults to FALSE.
   * @param bool $check_access
   *   (optional) Whether to check access on the loaded entities or not.
   *   Defaults to TRUE.
   *
   * @return \Drupal\jsonapi\JsonApiResource\ResourceObjectData
   *   A ResourceObjectData object containing a resource object with unlimited
   *   cardinality. This corresponds to a top-level document's primary
   *   data on a collection response.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  private function loadResourceObjectsByEntityIds($entity_type_id, array $ids, $load_latest_revisions = FALSE, $check_access = TRUE): ResourceObjectData {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    if ($load_latest_revisions) {
      assert($storage instanceof RevisionableStorageInterface);
      $entities = $storage->loadMultipleRevisions(array_keys($ids));
    }
    else {
      $entities = $storage->loadMultiple($ids);
    }
    return $this->createCollectionDataFromEntities($entities, $check_access);
  }

  /**
   * Apply sorting from the `sort` query parameter to the entity query.
   *
   * This is a no-op when no `sort` parameter is present, so subclasses can
   * call it unconditionally from their `process()` methods.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheability
   *   The cache metadata.
   *
   * @throws \Drupal\Core\Http\Exception\CacheableBadRequestHttpException
   *   When the `sort` parameter cannot be parsed, when a sort path does not
   *   resolve, or when the route has no resource type matching the query's
   *   entity type.
   */
  protected function applySortingToQuery(Request $request, QueryInterface $query, CacheableMetadata $cacheability): void {
    if (!$request->query->has(Sort::KEY_NAME)) {
      return;
    }

    $resource_type = $this->resolveQueryResourceType($request, $query, $cacheability);

    try {
      $sort = Sort::createFromQueryParameter($request->query->all()[Sort::KEY_NAME]);

      // Resolve each public field path to its internal entity-query path, the
      // same way core JSON:API does on its own collection routes, so sort
      // paths support field aliases and relationship traversal.
      foreach ($sort->fields() as $field) {
        $path = $this->fieldResolver->resolveInternalEntityQueryPath($resource_type, $field[Sort::PATH_KEY]);
        $query->sort($path, $field[Sort::DIRECTION_KEY] ?? 'ASC', $field[Sort::LANGUAGE_KEY] ?? NULL);
      }
    }
    catch (CacheableBadRequestHttpException $exception) {
      // Already a cacheable bad-request; propagate without re-wrapping so the
      // original message and cacheability are preserved.
      throw $exception;
    }
    catch (\UnexpectedValueException | \InvalidArgumentException $exception) {
      throw new CacheableBadRequestHttpException($cacheability, $exception->getMessage());
    }
  }

  /**
   * Apply filters from the `filter` query parameter to the entity query.
   *
   * This is a no-op when no `filter` parameter is present, so subclasses can
   * call it unconditionally from their `process()` methods.
   *
   * Field-level and entity-level filter access is enforced via
   * TemporaryQueryGuard, mirroring the protections core JSON:API applies to
   * its own collection routes.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheability
   *   The cache metadata.
   *
   * @throws \Drupal\Core\Http\Exception\CacheableBadRequestHttpException
   *   When the `filter` parameter cannot be parsed, or when the route has no
   *   resource type matching the query's entity type.
   */
  protected function applyFiltersToQuery(Request $request, QueryInterface $query, CacheableMetadata $cacheability): void {
    if (!$request->query->has(Filter::KEY_NAME)) {
      return;
    }

    $resource_type = $this->resolveQueryResourceType($request, $query, $cacheability);

    try {
      $filter = Filter::createFromQueryParameter(
        $request->query->all(Filter::KEY_NAME),
        $resource_type,
        $this->fieldResolver
      );

      // Apply user-supplied filter conditions, then apply access controls on
      // top so the filter cannot be used to probe entities or fields the
      // current user is not permitted to query against.
      $query->condition($filter->queryCondition($query));
      TemporaryQueryGuard::setFieldManager($this->fieldManager);
      TemporaryQueryGuard::setModuleHandler($this->moduleHandler);
      TemporaryQueryGuard::applyAccessControls($filter, $query, $cacheability);
    }
    catch (CacheableBadRequestHttpException $exception) {
      // Already a cacheable bad-request; propagate without re-wrapping so the
      // original message and cacheability are preserved.
      throw $exception;
    }
    catch (\UnexpectedValueException | \InvalidArgumentException $exception) {
      throw new CacheableBadRequestHttpException($cacheability, $exception->getMessage());
    }
  }

  /**
   * Selects the resource type used to resolve filter and sort fields.
   *
   * The route may declare multiple resource types via
   * `_jsonapi_resource_types`. Pick the one whose entity type matches the
   * query's base entity type; that's the type against which filter and sort
   * paths must resolve.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The entity query.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheability
   *   Cache metadata for the bad-request response if no match is found.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceType
   *   The resource type to use for filter and sort resolution.
   *
   * @throws \Drupal\Core\Http\Exception\CacheableBadRequestHttpException
   *   When the route declares no resource type for the query's entity type.
   */
  private function resolveQueryResourceType(Request $request, QueryInterface $query, CacheableMetadata $cacheability): ResourceType {
    $route_resource_types = $request->attributes->get('resource_types') ?: [];
    $entity_type_id = $query->getEntityTypeId();
    foreach ($route_resource_types as $candidate) {
      if ($candidate instanceof ResourceType && $candidate->getEntityTypeId() === $entity_type_id) {
        return $candidate;
      }
    }
    throw new CacheableBadRequestHttpException(
      $cacheability,
      sprintf('The route declares no JSON:API resource type for the entity type "%s" used by this query.', $entity_type_id)
    );
  }

}
