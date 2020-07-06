<?php

namespace Drupal\intercept_event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides suggested events.
 */
interface SuggestedEventsProviderInterface {

  /**
   * Gets all suggested event entities for an account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. Defaults to the current user.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of event entities.
   */
  public function getSuggestedEvents(AccountInterface $account = NULL);

  /**
   * Gets all suggested event entities for an event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $event
   *   The event.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of event entities.
   */
  public function getSuggestedEventsByEvent(EntityInterface $event);

}
