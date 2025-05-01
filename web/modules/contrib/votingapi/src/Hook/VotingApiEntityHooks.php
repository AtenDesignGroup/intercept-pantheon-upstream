<?php

declare(strict_types=1);

namespace Drupal\votingapi\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\votingapi\VoteInterface;

/**
 * Hook implementations used to create and dispatch Entity Events.
 */
final class VotingApiEntityHooks {

  /**
   * Constructs a new VotingApiEntityHooks service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Implements hook_entity_delete().
   */
  #[Hook('entity_delete')]
  public function entityDelete(EntityInterface $entity): void {
    // Only act on content entities.
    if (!($entity instanceof FieldableEntityInterface)) {
      return;
    }
    // Delete all votes and result entries for the deleted entities.
    if (!($entity instanceof VoteInterface)) {
      $vote_storage = $this->entityTypeManager->getStorage('vote');
      $vote_storage->deleteVotesForDeletedEntity($entity->getEntityTypeId(), $entity->id());
    }
  }

}
