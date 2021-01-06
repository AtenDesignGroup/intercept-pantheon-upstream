<?php

namespace Drupal\intercept_room_reservation;

use Drupal\Core\Session\AccountInterface;

/**
 * Provides room reservations.
 */
interface RoomReservationProviderInterface {

  /**
   * Gets all room reservation entities for an account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. Defaults to the current user.
   *
   * @return \Drupal\intercept_room_reservation\Entity\RoomReservationInterface[]
   *   An array of room reservation entities.
   */
  public function getRoomReservations(AccountInterface $account = NULL);

  /**
   * Gets all room reservation ids for an account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. Defaults to the current user.
   *
   * @return int[]
   *   An array of room reservation ids.
   */
  public function getRoomReservationIds(AccountInterface $account = NULL);

}
