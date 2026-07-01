<?php

declare(strict_types=1);

namespace Drupal\jsonapi_resources\Unstable\Entity\Query;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\jsonapi_resources\Entity\Query\PaginatorMetadata;

/**
 * Executes entity queries and captures cacheability.
 */
final class CacheabilityCapturingExecutor {

  /**
   * A renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * EntityQueryExecutor constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   A renderer.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * Executes the query in a render context, to catch bubbled cacheability.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query to execute to get the return results.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $cacheable_metadata
   *   An refinable cacheable dependency with which to capture cacheability.
   *
   * @return int|array
   *   Returns an integer for count queries or an array of IDs. The values of
   *   the array are always entity IDs. The keys will be revision IDs if the
   *   entity supports revision and entity IDs if not.
   *
   * @see node_query_node_access_alter()
   * @see https://www.drupal.org/project/drupal/issues/2557815
   * @see https://www.drupal.org/project/drupal/issues/2794385
   * @todo Remove this after https://www.drupal.org/project/drupal/issues/3028976 is fixed.
   */
  public function executeQueryAndCaptureCacheability(QueryInterface $query, RefinableCacheableDependencyInterface $cacheable_metadata) {
    $context = new RenderContext();
    $results = $this->renderer->executeInRenderContext($context, function () use ($query) {
      return $query->accessCheck(TRUE)->execute();
    });
    $paginator_metadata = $query->getMetaData(PaginatorMetadata::KEY);
    if (is_array($results) && $paginator_metadata instanceof PaginatorMetadata && count($results) > (int) $paginator_metadata->pageSizeMax) {
      $paginator_metadata->hasNextPage = TRUE;
      array_pop($results);
    }
    if (!$context->isEmpty()) {
      $cacheable_metadata->addCacheableDependency($context->pop());
    }
    return $results;
  }

  /**
   * Executes a database select in a render context, to catch cacheability.
   *
   * Parallels self::executeQueryAndCaptureCacheability() for raw
   * \Drupal\Core\Database\Query\SelectInterface queries — the row shape is
   * caller-defined (not entity IDs), so the result is the raw fetched rows.
   *
   * If the query carries pagination metadata (see PaginatorMetadata), this
   * method applies the same "fetched N+1, pop one, mark next-page" logic the
   * entity-query path uses.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The select query to execute. Should already have any pagination range
   *   and metadata applied (see OffsetLimitPaginator::applyToQuery()).
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $cacheable_metadata
   *   Refinable cacheable dependency to capture cacheability into.
   *
   * @return array
   *   The fetched rows as associative arrays. The caller is responsible for
   *   extracting any entity-ID column from each row.
   */
  public function executeSelectAndCaptureCacheability(SelectInterface $query, RefinableCacheableDependencyInterface $cacheable_metadata): array {
    $context = new RenderContext();
    $results = $this->renderer->executeInRenderContext($context, function () use ($query) {
      return $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    });
    $paginator_metadata = $query->getMetaData(PaginatorMetadata::KEY);
    if ($paginator_metadata instanceof PaginatorMetadata && count($results) > (int) $paginator_metadata->pageSizeMax) {
      $paginator_metadata->hasNextPage = TRUE;
      array_pop($results);
    }
    if (!$context->isEmpty()) {
      $cacheable_metadata->addCacheableDependency($context->pop());
    }
    return $results;
  }

  /**
   * Executes a count query derived from a select, capturing cacheability.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The select query whose total result count is wanted. Not mutated; a
   *   derived count query is built from a clone.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $cacheable_metadata
   *   Refinable cacheable dependency to capture cacheability into.
   *
   * @return int
   *   The total number of matched rows.
   */
  public function executeSelectCountAndCaptureCacheability(SelectInterface $query, RefinableCacheableDependencyInterface $cacheable_metadata): int {
    // The query arrives with the paginator's range (LIMIT $size + 1) applied.
    // SelectInterface::countQuery() preserves that range, which would cap the
    // count at the page size, so clear it on a clone first. This mirrors the
    // entity-query path, which counts with range(NULL, NULL).
    $count_query = clone $query;
    $count_query->range(NULL, NULL);
    $context = new RenderContext();
    $count = $this->renderer->executeInRenderContext($context, function () use ($count_query) {
      return $count_query->countQuery()->execute()->fetchField();
    });
    if (!$context->isEmpty()) {
      $cacheable_metadata->addCacheableDependency($context->pop());
    }
    return (int) $count;
  }

}
