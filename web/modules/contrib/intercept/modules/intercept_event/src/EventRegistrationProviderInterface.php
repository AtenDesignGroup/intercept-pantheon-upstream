<?php

namespace Drupal\intercept_event;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Provides event registrations.
 */
interface EventRegistrationProviderInterface {

  /**
   * Gets all event registration entities for an account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. Defaults to the current user.
   *
   * @return \Drupal\intercept_event\Entity\EventRegistrationInterface[]
   *   An array of event registration entities.
   */
  public function getEventRegistrations(AccountInterface $account = NULL);

  /**
   * Gets all event registration ids for an account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. Defaults to the current user.
   *
   * @return int[]
   *   An array of event registration ids.
   */
  public function getEventRegistrationIds(AccountInterface $account = NULL);

  /**
   * Gets all event registration entities for an account.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event Node.
   *
   * @return \Drupal\intercept_event\Entity\EventRegistrationInterface[]
   *   An array of event registration entities.
   */
  public function getEventRegistrationsByEvent(NodeInterface $event);

  /**
   * Gets all event registration ids for an account.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event Node.
   *
   * @return int[]
   *   An array of event registration ids.
   */
  public function getEventRegistrationIdsByEvent(NodeInterface $event);

}
