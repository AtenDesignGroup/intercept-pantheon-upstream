<?php

namespace Drupal\votingapi;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines an interface for vote entity storage classes.
 */
interface VoteStorageInterface extends EntityStorageInterface {

  /**
   * Gets votes for a user.
   *
   * @param int $uid
   *   User ID.
   * @param ?string $vote_type_id
   *   (optional) The vote type ID.
   * @param ?string $entity_type_id
   *   (optional) The voted entity type ID.
   * @param string|int|null $entity_id
   *   (optional) The voted entity ID.
   * @param ?string $vote_source
   *   (optional) The vote source, only used if $uid == 0.
   *
   * @return \Drupal\votingapi\VoteInterface[]
   *   Returns the user votes.
   */
  public function getUserVotes(int $uid, ?string $vote_type_id = NULL, ?string $entity_type_id = NULL, string|int|null $entity_id = NULL, ?string $vote_source = NULL): array;

  /**
   * Deletes votes for a user.
   *
   * @param int $uid
   *   The User ID.
   * @param ?string $vote_type_id
   *   (optional) The vote type ID.
   * @param ?string $entity_type_id
   *   (optional) The voted entity type ID.
   * @param string|int|null $entity_id
   *   (optional) The voted entity ID.
   * @param ?string $vote_source
   *   (optional) The vote source, only used if $uid == 0.
   */
  public function deleteUserVotes(int $uid, ?string $vote_type_id = NULL, ?string $entity_type_id = NULL, string|int|null $entity_id = NULL, ?string $vote_source = NULL): void;

  /**
   * Returns the default vote source.
   *
   * @param ?string $vote_source
   *   (optional) The vote source.
   *
   * @return string
   *   The $vote_source parameter or, if it is NULL, the default vote source.
   */
  public static function defaultVoteSource(?string $vote_source = NULL): string;

  /**
   * Gets votes since a determined moment.
   *
   * @return \Drupal\votingapi\VoteInterface[]
   *   Returns array of votes since last cron run.
   */
  public function getVotesSinceMoment(): array;

  /**
   * Deletes votes for deleted entity everywhere in the database.
   *
   * @param string $entity_type_id
   *   The voted entity type ID.
   * @param string|int $entity_id
   *   The voted entity ID.
   */
  public function deleteVotesForDeletedEntity(string $entity_type_id, string|int $entity_id): void;

}
