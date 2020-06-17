<?php

/**
 * @file
 * Generate votingapi votes, recalculate results for existing votes, or flush
 *  VotingAPI data entirely.
 */

use Drupal\votingapi\Entity\Vote;

/**
 * Implements of hook_drush_help().
 */
function votingapi_drush_help($section) {
  switch ($section) {
    case 'drush:generate-votes':
      return dt('Usage: drush generate-votes <entity_type> <vote_type>.');

    case 'drush:votingapi-recalculate':
      return dt('Usage: drush votingapi-recalculate <entity_type>.');

    case 'drush:votingapi-flush':
      return dt('Usage: drush votingapi-flush <entity_type>.');
  }
}

/**
 * Implements of hook_drush_command().
 */
function votingapi_drush_command() {
  $items['generate-votes'] = [
    'description' => 'Creates dummy voting data.',
    'arguments' => [
      'entity_type' => 'The type of entity to generate votes for.',
      'vote_type' => 'The type of votes to generate, defaults to \'percent\'.',
    ],
    'options' => [
      'kill_votes' => 'Specify \'kill_votes\' to delete all existing votes before generating new ones.',
      'age' => 'The maximum age, in seconds, of each vote.',
      'node_types' => 'A comma delimited list of node types to generate votes for, if the entity type is \'node\'.',
    ],
    'aliases' => ['genv'],
  ];
  $items['votingapi-recalculate'] = [
    'description' => 'Regenerates voting results from raw vote data.',
  // Various D7 code assumes we have a uid.
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN,
    'arguments' => [
      'entity_type' => 'The type of entity to recalculate vote results for.',
    ],
    'aliases' => ['vcalc'],
  ];
  $items['votingapi-flush'] = [
    'description' => 'Deletes all existing voting data.',
  // Various D7 code assumes we have a uid.
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN,
    'arguments' => [
      'entity_type' => 'The type of entity whose voting data should be flushed.',
    ],
    'aliases' => ['vflush'],
  ];
  return $items;
}

/**
 * Command callback. Generate a number of votes.
 */
function drush_votingapi_generate_votes($entity_type = 'node', $vote_type = 'percent') {
  $options = [
    'kill_votes' => drush_get_option('kill_votes'),
    'age' => drush_get_option('age'),
    'node_types' => drush_get_option('node_types'),
  ];

  votingapi_generate_votes($entity_type, $vote_type, $options);

  drush_log(dt('Generating @vtype votes for @etype entities.', ['@vtype' => $vote_type, '@etype' => $entity_type]), 'success');
}

/**
 * Utility function to generate votes.
 */
function votingapi_generate_votes($entity_type = 'node', $vote_type = 'percent', $options = []) {
  $options += [
    'age' => 36000,
    'node_types' => [],
    'kill_votes' => FALSE,
  ];
  if (!empty($options['kill_votes'])) {
    $cache = \Drupal::database()->delete('votingapi_result');
    $votes = \Drupal::database()->delete('votingapi_vote');
    $cache->condition('entity_type', $entity_type);
    $votes->condition('entity_type', $entity_type);
    $cache->execute();
    $votes->execute();
  }
  $uids = \Drupal::entityQuery('user')
    ->condition('status', 1)
    ->execute();
  $query = \Drupal::database()->select($entity_type, 'e')
    ->fields('e', ['nid']);
  if ($entity_type == 'node' && !empty($options['types'])) {
    $query->condition('e.type', $options['types'], 'IN');
  }
  $results = $query->execute()->fetchAll(PDO::FETCH_ASSOC);
  foreach ($results as $entity) {
    _votingapi_cast_votes($entity_type, $entity['nid'], $options['age'], $uids, $vote_type);
  }
}

/**
 * Utility function to generate votes on a node by a set of users.
 */
function _votingapi_cast_votes($eType, $eId, $timestamp = 0, $uids = [], $style = 'percent') {
  foreach ($uids as $uid) {
    $request_time = \Drupal::time()->getRequestTime();
    $value = $style === 'points' ? rand(0, 1) ? 1 : -1 : mt_rand(1, 5) * 20;
    $vote = Vote::create(['type' => 'vote']);
    $vote->setVotedEntityId($eId);
    $vote->setVotedEntityType($eType);
    $vote->setOwnerId($uid);
    $vote->setCreatedTime($request_time - mt_rand(0, $timestamp));
    $vote->setValueType($style);
    $vote->setValue($value);
    $vote->save();
  }
}

/**
 * Command callback. Recalculate voting results.
 */
function drush_votingapi_recalculate($entity_type = 'node', $entity_id = NULL, $vote_type = 'vote') {
  // Prep some starter query objects.
  if (empty($entity_id)) {
    $votes = \Drupal::database()->select('votingapi_vote', 'vv')
      ->fields('vv', ['entity_type', 'entity_id'])
      ->condition('entity_type', $entity_type, '=')
      ->distinct(TRUE)
      ->execute()->fetchAll(PDO::FETCH_ASSOC);
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
    $manager = Drupal::service('plugin.manager.votingapi.resultfunction');
    $manager->recalculateResults($vote['entity_type'], $vote['entity_id'], $vote_type);
  }

  drush_log($message, 'success');
}

/**
 * Command callback. Flush votes and results.
 */
function drush_votingapi_flush($entity_type = NULL, $entity_id = NULL) {
  if (drush_confirm(dt("Delete @type voting data?", ['@type' => empty($entity_type) ? dt('all') : $entity_type]))) {
    $cache = \Drupal::database()->delete('votingapi_result');
    $votes = \Drupal::database()->delete('votingapi_vote');

    if (!empty($entity_type)) {
      $cache->condition('entity_type', $entity_type);
      $votes->condition('entity_type', $entity_type);
    }
    if (!empty($entity_id)) {
      $cache->condition('entity_id', $entity_id);
      $votes->condition('entity_id', $entity_id);
    }

    $cache->execute();
    $votes->execute();

    drush_log(dt('Flushed vote data for @type entities.', ['@type' => empty($entity_type) ? t('all') : $entity_type]), 'success');
  }
}
