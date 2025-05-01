<?php

declare(strict_types=1);

namespace Drupal\votingapi\Hook;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;
use Drupal\votingapi\VoteResultFunctionManagerInterface;

/**
 * Hook implementations used for scheduled execution.
 */
final class VotingApiCronHooks {

  /**
   * Constructs a new VotingApiCronHooks service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Component\Datetime\TimeInterface $datetime
   *   The datetime.time service.
   * @param \Drupal\votingapi\VoteResultFunctionManagerInterface $voteResultFunctionManager
   *   The plugin manager for VoteResultFunction plugins.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected StateInterface $state,
    protected TimeInterface $datetime,
    protected VoteResultFunctionManagerInterface $voteResultFunctionManager,
  ) {}

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    if ($this->configFactory->get('votingapi.settings')->get('calculation_schedule') === 'cron') {
      $vote_storage = $this->entityTypeManager->getStorage('vote');
      $results = $vote_storage->getVotesSinceMoment();
      foreach ($results as $entity) {
        $this->voteResultFunctionManager->recalculateResults(
          $entity['entity_type'],
          $entity['entity_id'],
          $entity['type']
        );
      }
      $this->state->set('votingapi.last_cron', $this->datetime->getRequestTime());
    }
  }

}
