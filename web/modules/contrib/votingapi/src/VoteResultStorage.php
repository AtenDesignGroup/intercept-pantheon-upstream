<?php

declare(strict_types=1);

namespace Drupal\votingapi;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Storage class for vote_result entities.
 *
 * This extends the \Drupal\entity\EntityDatabaseStorage class, adding
 * required special handling for vote_result entities.
 */
class VoteResultStorage extends SqlContentEntityStorage implements VoteResultStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getEntityResults(string $entity_type_id, string|int $entity_id, string $vote_type, string $function): array {
    $query = $this->getQuery()
      ->condition('entity_type', $entity_type_id)
      ->condition('entity_id', $entity_id)
      ->condition('type', $vote_type);
    if (!empty($function)) {
      $query->condition('function', $function);
    }
    $query->sort('type');
    $query->accessCheck(TRUE);
    $vote_ids = $query->execute();
    return $this->loadMultiple($vote_ids);
  }

}
