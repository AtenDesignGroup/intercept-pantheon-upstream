<?php

namespace Drupal\votingapi;

/**
 * Manages vote result function plugins.
 */
interface VoteResultFunctionManagerInterface {

  /**
   * Get the voting results for an entity.
   *
   * @param string $entity_type_id
   *   The type of entity, e.g. 'node'.
   * @param string|int $entity_id
   *   The ID of the entity.
   *
   * @return array
   *   A nested array
   */
  public function getResults(string $entity_type_id, string|int $entity_id): array;

  /**
   * Recalculates the aggregate voting results of all votes for a given entity.
   *
   * Loads all votes for a given piece of content, then calculates and caches
   * the aggregate vote results. This is only intended for modules that have
   * assumed responsibility for the full voting cycle: the votingapi_set_vote()
   * function recalculates automatically.
   *
   * @param string $entity_type_id
   *   A string identifying the type of content being rated. Node, comment,
   *   aggregator item, etc.
   * @param string|int $entity_id
   *   The key ID of the content being rated.
   * @param string $vote_type
   *   The type of vote cast.
   */
  public function recalculateResults(string $entity_type_id, string|int $entity_id, string $vote_type): void;

}
