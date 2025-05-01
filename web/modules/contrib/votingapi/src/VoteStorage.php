<?php

declare(strict_types=1);

namespace Drupal\votingapi;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\votingapi\Entity\Vote;

/**
 * Storage class for vote entities.
 */
class VoteStorage extends SqlContentEntityStorage implements VoteStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getUserVotes(int $uid, ?string $vote_type_id = NULL, ?string $entity_type_id = NULL, string|int|null $entity_id = NULL, ?string $vote_source = NULL): array {
    $query = $this->getQuery()
      ->accessCheck(TRUE)
      ->condition('user_id', $uid);
    if ($vote_type_id) {
      $query->condition('type', $vote_type_id);
    }
    if ($entity_type_id) {
      $query->condition('entity_type', $entity_type_id);
    }
    if ($entity_id) {
      $query->condition('entity_id', $entity_id);
    }
    if ($uid == 0) {
      $query->condition('vote_source', static::defaultVoteSource($vote_source));
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteUserVotes(int $uid, ?string $vote_type_id = NULL, ?string $entity_type_id = NULL, string|int|null $entity_id = NULL, ?string $vote_source = NULL): void {
    $votes = $this->getUserVotes($uid, $vote_type_id, $entity_type_id, $entity_id, $vote_source);
    if (!empty($votes)) {
      $entities = $this->loadMultiple($votes);
      $this->delete($entities);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultVoteSource(?string $vote_source = NULL): string {
    if (is_null($vote_source)) {
      $vote = Vote::create(['type' => 'vote']);
      $callback = $vote->getFieldDefinition('vote_source')
        ->getDefaultValueCallback();
      $vote_source = $callback();
    }
    return $vote_source;
  }

  /**
   * {@inheritdoc}
   */
  public function getVotesSinceMoment(): array {
    $last_cron = \Drupal::state()->get('votingapi.last_cron', 0);
    return $this->getAggregateQuery()
      ->condition('timestamp', $last_cron, '>')
      ->groupBy('entity_type')
      ->groupBy('entity_id')
      ->groupBy('type')
      ->accessCheck(TRUE)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteVotesForDeletedEntity(string $entity_type_id, string|int $entity_id): void {
    $votes = $this->getQuery()
      ->accessCheck(TRUE)
      ->condition('entity_type', $entity_type_id)
      ->condition('entity_id', $entity_id)
      ->execute();
    if (!empty($votes)) {
      $entities = $this->loadMultiple($votes);
      $this->delete($entities);
    }
    $this->database->delete('votingapi_result')
      ->condition('entity_type', $entity_type_id)
      ->condition('entity_id', $entity_id)
      ->execute();
    if ($entity_type_id == 'user' && \Drupal::config('votingapi.settings')->get('delete_everywhere')) {
      $this->deleteUserVotes($entity_id);
    }
  }

}
