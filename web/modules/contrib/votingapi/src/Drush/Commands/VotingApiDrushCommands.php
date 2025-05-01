<?php

namespace Drupal\votingapi\Drush\Commands;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\votingapi\Entity\Vote;
use Drupal\votingapi\VoteResultFunctionManagerInterface;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;

// cspell:ignore genv vcalc vflush vtype etype resultfunction

/**
 * Drush 12+ commands for the Voting API module.
 *
 * Generates Voting API votes, recalculates results for existing votes, or
 * flushes Voting API data entirely.
 */
final class VotingApiDrushCommands extends DrushCommands {
  use AutowireTrait;

  /**
   * Constructs the VotingApiDrushCommands object.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $timeService
   *   The datetime.time service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\votingapi\VoteResultFunctionManagerInterface $voteResultFunctionManager
   *   The plugin.manager.votingapi.resultfunction service.
   */
  public function __construct(
    protected TimeInterface $timeService,
    protected Connection $database,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected VoteResultFunctionManagerInterface $voteResultFunctionManager,
  ) {
    parent::__construct();
  }

  /**
   * Creates dummy voting data.
   *
   * @param string $entity_type
   *   The type of entity to generate votes for.
   * @param string $vote_type
   *   (optional) The type of votes to generate, defaults to 'vote'.
   * @param array $options
   *   (optional) An associative array of options.
   *
   * @command voting:generate
   * @aliases genv,generate-votes
   *
   * @option kill_votes
   *   Specify 'kill_votes' to delete all existing votes before generating
   *   new ones.
   * @option age
   *   The maximum age, in seconds, of each vote.
   * @option node_types
   *   A comma-delimited list of node types to generate votes for, if the entity
   *   type is 'node'.
   *
   * @usage drush voting:generate [entity_type]
   *   Creates dummy voting data for the specified entity type.
   */
  #[CLI\Command(name: 'voting:generate', aliases: ['genv', 'generate-votes'])]
  #[CLI\Help(description: 'Creates dummy voting data.')]
  #[CLI\Argument(name: 'entity_type', description: 'The type of entity to generate votes for.')]
  #[CLI\Argument(name: 'vote_type', description: "The type of votes to generate, defaults to 'vote'.")]
  #[CLI\Usage(name: 'drush voting:generate [entity_type]', description: 'Creates dummy voting data for the specified entity type.')]
  public function votes(string $entity_type, string $vote_type = 'vote', array $options = []): void {
    $options += [
      'kill_votes' => NULL,
      'age' => NULL,
      'node_types' => NULL,
    ];

    $this->generateVotes($entity_type, $vote_type, $options);

    $this->logger->success(dt('Generated @vtype votes for @etype entities.', [
      '@vtype' => $vote_type,
      '@etype' => $entity_type,
    ]));
  }

  /**
   * Regenerates voting results from raw vote data.
   *
   * @param string $entity_type
   *   (optional) The type of entity to recalculate vote results for.
   * @param string $vote_type
   *   (optional) The type of votes to recalculate, defaults to 'percent'.
   * @param string $entity_id
   *   (optional) The ID of the entity.
   *
   * @command voting:recalculate
   * @aliases vcalc,votingapi-recalculate
   *
   * @usage drush voting:recalculate
   *  Regenerates voting results from raw vote data for node entities (default).
   * @usage drush voting:recalculate comment
   *  Regenerates voting results from raw vote data for comment entities.
   */
  #[CLI\Command(name: 'voting:recalculate', aliases: ['vcalc', 'votingapi-recalculate'])]
  #[CLI\Help(description: 'Regenerates voting results from raw vote data.')]
  #[CLI\Argument(name: 'entity_type', description: 'The type of entity to recalculate vote results for.')]
  #[CLI\Argument(name: 'vote_type', description: "The type of votes to recalculate, defaults to 'percent'.")]
  #[CLI\Argument(name: 'entity_id', description: 'The ID of the entity.')]
  #[CLI\Usage(name: 'drush voting:recalculate [entity_type]', description: "Regenerates voting results from raw vote data. Defaults to 'node'.")]
  public function recalculate(string $entity_type = 'node', string $vote_type = 'vote', ?string $entity_id = NULL): void {
    // Prep some starter query objects.
    if (empty($entity_id)) {
      $votes = $this->database->select('votingapi_vote', 'vv')
        ->fields('vv', ['entity_type', 'entity_id'])
        ->condition('entity_type', $entity_type, '=')
        ->distinct(TRUE)
        ->execute()->fetchAll(\PDO::FETCH_ASSOC);
      $message = dt('Rebuilt voting results for @type votes.', ['@type' => $entity_type]);
    }
    else {
      $votes[] = ['entity_type' => $entity_type, 'entity_id' => $entity_id];
      $message = dt('Rebuilt voting results for @type id: @entity_id.', [
        '@type' => $entity_type,
        '@entity_id' => $entity_id,
      ]);
    }

    foreach ($votes as $vote) {
      $this->voteResultFunctionManager->recalculateResults($vote['entity_type'], $vote['entity_id'], $vote_type);
    }

    $this->logger->success($message);
  }

  /**
   * Deletes all existing voting data.
   *
   * @param string $entity_type
   *   (optional) The type of entity whose voting data should be flushed.
   * @param string $entity_id
   *   (optional) The ID of the entity.
   *
   * @command voting:flush
   * @aliases vflush,votingapi-flush
   *
   * @usage drush voting:flush [entity_type | 'all']
   *  Deletes all existing voting data for the specified entity type.
   */
  #[CLI\Command(name: 'voting:flush', aliases: ['vflush', 'votingapi-flush'])]
  #[CLI\Help(description: 'Deletes all existing voting data.')]
  #[CLI\Argument(name: 'entity_type', description: 'The type of entity whose voting data should be flushed.')]
  #[CLI\Argument(name: 'entity_id', description: 'The ID of the entity.')]
  #[CLI\Usage(name: "drush voting:flush [entity_type | 'all']", description: 'Deletes all existing voting data for the specified entity type.')]
  public function flush(string $entity_type = 'all', ?string $entity_id = NULL): void {
    if ($this->io()->confirm(dt("Delete @type voting data?", ['@type' => $entity_type]))) {
      $cache = $this->database->delete('votingapi_result');
      $votes = $this->database->delete('votingapi_vote');

      if ($entity_type !== 'all') {
        $cache->condition('entity_type', $entity_type);
        $votes->condition('entity_type', $entity_type);
      }
      if (!empty($entity_id)) {
        $cache->condition('entity_id', $entity_id);
        $votes->condition('entity_id', $entity_id);
      }

      $cache->execute();
      $votes->execute();

      $this->logger->success(dt('Flushed vote data for @type entities.', ['@type' => $entity_type]));
    }
  }

  /**
   * Utility method to generate votes.
   *
   * @param string $entity_type
   *   (optional) The type of entity to generate votes for.
   * @param string $vote_type
   *   (optional) The type of votes to generate, defaults to 'percent'.
   * @param array $options
   *   (optional) An associative array of options.
   */
  protected function generateVotes(string $entity_type = 'node', string $vote_type = 'percent', array $options = []): void {
    $options += [
      'age' => 36000,
      'node_types' => [],
      'kill_votes' => FALSE,
    ];
    if (!empty($options['kill_votes'])) {
      $this->database->delete('votingapi_result')
        ->condition('entity_type', $entity_type)
        ->execute();
      $this->database->delete('votingapi_vote')
        ->condition('entity_type', $entity_type)
        ->execute();
    }
    /** @var \Drupal\Core\Entity\Query\QueryInterface $user_query */
    $user_query = $this->entityTypeManager->getStorage('user')->getQuery();
    $uids = $user_query
      ->accessCheck(TRUE)
      ->condition('status', 1)
      ->execute();
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->database->select($entity_type, 'e')
      ->fields('e', ['nid']);
    if ($entity_type == 'node' && !empty($options['types'])) {
      $query->condition('e.type', $options['types'], 'IN');
    }
    $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($results as $entity) {
      $this->castVotes($entity_type, $entity['nid'], $options['age'], $uids, $vote_type);
    }
  }

  /**
   * Utility method to generate votes on a node by a set of users.
   *
   * @param string $entity_type
   *   (optional) The type of entity to recalculate vote results for.
   * @param string $entity_id
   *   (optional) The ID of the entity.
   * @param int $timestamp
   *   (optional) The timestamp to use for the generated vote.
   * @param array $uids
   *   (optional) An array of user IDs to use for the generated votes.
   * @param string $style
   *   (optional) Vote style. Defaults to 'percent'.
   */
  protected function castVotes(string $entity_type, string $entity_id, int $timestamp = 0, array $uids = [], string $style = 'percent'): void {
    foreach ($uids as $uid) {
      $request_time = $this->timeService->getRequestTime();
      $value = $style === 'points' ? rand(0, 1) ? 1 : -1 : mt_rand(1, 5) * 20;
      $vote = Vote::create(['type' => 'vote']);
      $vote->setVotedEntityId($entity_id);
      $vote->setVotedEntityType($entity_type);
      $vote->setOwnerId($uid);
      $vote->setCreatedTime($request_time - mt_rand(0, $timestamp));
      $vote->setValueType($style);
      $vote->setValue($value);
      $vote->save();
    }
  }

}
