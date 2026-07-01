<?php

namespace Drupal\jsonapi_resources\Entity\Query;

use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\Query\ConditionInterface;
use Drupal\Core\Entity\Query\Null\Condition;
use Drupal\Core\Entity\Query\QueryBase;

/**
 * An adapter to enable JSON:API queries against data not sourced from entities.
 *
 * JSON:API logic in Core and the JSON:API Resources module are both designed to
 * apply queries to Drupal entity queries. Normally, this makes it very
 * difficult to supply data to these APIs unless it comes from a Drupal entity.
 * This adapter works around this issue by providing a way to wrap any generic
 * programmatic query into an interface that behaves like an entity query.
 *
 * Using this adapter, JSON:API can do all the work of transcribing conditions
 * and range restrictions from an incoming JSON:API request to the target query,
 * generate a result set, and then return it as a JSON:API-compliant JSON
 * payload, without actually having to work with an entity in the local
 * database.
 *
 * Grouped and nested conditions are NOT evaluated by this base class:
 * conditionGroupFactory() returns a no-op condition, so condition groups
 * created via andConditionGroup()/orConditionGroup() are recorded but never
 * applied. Subclasses are responsible for inspecting every condition
 * themselves (typically by walking getCondition()->conditions()) and must
 * decide how (or whether) to support grouped conditions.
 */
abstract class NonEntityQueryBase extends QueryBase {

  /**
   * Constructs a new instance.
   *
   * QueryInterface requires an entity type, even though this adapter exposes
   * data that is not stored as entities. Rather than borrow an unrelated real
   * entity type, a lightweight EntityType is built from the given ID, so the
   * adapter never depends on a particular entity type existing in the
   * installation. Pick an ID that describes the data being queried.
   *
   * @param string $entity_type_id
   *   A machine name describing the data this query returns. It does not have
   *   to match a real entity type.
   * @param string $conjunction
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   */
  public function __construct(
    string $entity_type_id,
    string $conjunction = 'AND',
  ) {
    parent::__construct(
      new EntityType(['id' => $entity_type_id]),
      $conjunction,
      [],
    );
  }

  /**
   * {@inheritdoc}
   *
   * @return int|array
   *   The result of the query, which will either be a count of records (for a
   *   count query), or a page of results. When results are being returned, the
   *   significance, structure, and level of detail in each element of the array
   *   is left up to the implementation, but it must match what the JSON:API
   *   resource that is invoking this query expects to receive. Some
   *   implementations may choose to return the IDs of objects that should be
   *   loaded, while others may return the entire set of fields that should be
   *   rendered back in the final payload.
   */
  final public function execute() {
    if ($this->isCount()) {
      $results = $this->fetchCountOfResults();
    }
    else {
      $paginator_metadata = $this->getMetaData(PaginatorMetadata::KEY);

      $results = $this->fetchResults($paginator_metadata);

      if (($paginator_metadata instanceof PaginatorMetadata) &&
          (count($results) > ((int) $paginator_metadata->pageSizeMax))) {
        $paginator_metadata->hasNextPage = TRUE;

        // We actually load one more than the page limit during each request.
        // So, if the client requested 50 records, we load 51. Then, if there
        // are 51 records, we know that there must be a next page.
        //
        // This approach is based on
        // \Drupal\jsonapi_resources\Unstable\Entity\Query\CacheabilityCapturingExecutor::executeQueryAndCaptureCacheability().
        //
        array_pop($results);
      }
    }

    return $results;
  }

  /**
   * Gets whether this is a count query.
   *
   * @return bool
   *   Either TRUE if this is a count query, or FALSE if it isn't.
   */
  protected function isCount(): bool {
    return $this->count;
  }

  /**
   * Gets the range specified for the query.
   *
   * @return int[]
   *   An array specifying the range of values to return:
   *     - start: The starting offset for returned values.
   *     - length: The maximum number of values to return.
   */
  protected function getRange(): array {
    return $this->range;
  }

  /**
   * Gets the conditions specified for the query.
   *
   * @return \Drupal\Core\Entity\Query\ConditionInterface
   *   The conditions of the query.
   */
  protected function getCondition(): ConditionInterface {
    return $this->condition;
  }

  /**
   * {@inheritdoc}
   *
   * Returns a no-op condition from the Null entity query backend. Condition
   * groups built with andConditionGroup()/orConditionGroup() are therefore
   * inert: they are recorded on the query but never evaluated. Subclasses that
   * need grouped or nested conditions must inspect getCondition()->conditions()
   * and apply that logic themselves.
   */
  protected function conditionGroupFactory($conjunction = 'AND') {
    return new Condition($conjunction, $this);
  }

  /**
   * Gets the total number of results (for a count query).
   *
   * Sub-classes must implement this method to provide the logic for calculating
   * the total number of records.
   *
   * @return int
   *   The total number of records.
   */
  abstract protected function fetchCountOfResults(): int;

  /**
   * Gets the current page of results, based on the supplied paginator metadata.
   *
   * Sub-classes must implement this method to provide the logic for fetching
   * the data to return through JSON:API.
   *
   * @param \Drupal\jsonapi_resources\Entity\Query\PaginatorMetadata|null $paginator_metadata
   *   Metadata supplied by JSON:API about the size and offset of the page being
   *   requested.
   *
   * @return array
   *   An array describing the results of the query. The significance,
   *   structure, and level of detail in each element of the array is left up to
   *   the implementation, but it must match what the JSON:API resource that is
   *   invoking this query expects to receive. Some implementations may choose
   *   to return the IDs of objects that should be loaded, while others may
   *   return the entire set of fields that should be rendered back in the final
   *   payload.
   */
  abstract protected function fetchResults(
    ?PaginatorMetadata $paginator_metadata,
  ): array;

}
