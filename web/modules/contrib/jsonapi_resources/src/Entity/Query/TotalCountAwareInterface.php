<?php

declare(strict_types=1);

namespace Drupal\jsonapi_resources\Entity\Query;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Reports the total result count for a paginated entity query.
 *
 * @see \Drupal\jsonapi_resources\Entity\Query\PaginatorInterface
 * @see \Drupal\jsonapi_resources\Resource\EntityQueryResourceBase::buildCountMeta()
 */
interface TotalCountAwareInterface {

  /**
   * Returns the total number of entities matching the executed query.
   *
   * Implementations must memoize. The `last`-page link path and a caller
   * asking for `meta.count` both invoke this method, and the count query
   * must run at most once per request.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface|\Drupal\Core\Database\Query\SelectInterface $executed_query
   *   The query the paginator was applied to. Already executed.
   *   Implementations may mutate it into a count query, so do not re-use it
   *   for fetching results after calling this method.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheable_metadata
   *   Cache metadata to capture from the count query. Callers add the same
   *   object to the cacheability of the final response.
   *
   * @return int
   *   The total number of matched entities.
   */
  public function getTotalCount(QueryInterface|SelectInterface $executed_query, CacheableMetadata $cacheable_metadata): int;

}
