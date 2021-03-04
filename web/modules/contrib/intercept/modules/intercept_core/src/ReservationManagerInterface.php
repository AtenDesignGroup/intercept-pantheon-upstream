<?php

namespace Drupal\intercept_core;

use Drupal\node\NodeInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Interface ReservationManagerInterface.
 */
interface ReservationManagerInterface {

  public const FORMAT = 'Y-m-d\TH:i:s';

  /**
   * Expose the date utility for functions that use this service.
   *
   * @return \Drupal\intercept_core\Utility\Dates
   *   The Intercept Dates utility.
   */
  public function dateUtility();

  /**
   * Get a reservation entity for an event node.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node entity.
   *
   * @return bool|\Drupal\intercept_room_reservation\Entity\RoomReservationInterface
   *   The Room Reservation entity, or FALSE.
   */
  public function getEventReservation(NodeInterface $event);

  /**
   * Determines the duration between a start and end date.
   *
   * @param string $start
   *   The start time.
   * @param string $end
   *   The end time.
   */
  public function duration(string $start, string $end);

  /**
   * Gets all Room Reservations keyed by room UUID.
   *
   * @param array $params
   *   The parameters to check availability for.
   *
   * @return array
   *   An array of Reservation entities keyed by room UUID.
   */
  public function roomReservationsByNode(array $params);

  /**
   * Determines if a start and end date conflicts with existing reservations.
   *
   * @param array $reservations
   *   An array of existing reservations.
   * @param array $params
   *   An array of reservation parameters.
   *
   * @return bool
   *   Whether a start and end date conflicts with existing reservations.
   */
  public function hasReservationConflict(array $reservations, array $params);

  /**
   * Determines if a start and end date conflicts with a Location's hours.
   *
   * Adds an aggressive check to reservation conflict.
   *
   * @param array $reservations
   *   An array of existing reservations.
   * @param array $params
   *   An array of reservation parameters.
   * @param \Drupal\node\NodeInterface $room
   *   A Room node.
   *
   * @return bool
   *   Whether a start and end date conflicts with a Location's hours.
   *
   * @TODO: Refactor the app to make multiple kinds of conflict checks.
   */
  public function aggressiveOpeningHoursConflict(array $reservations, array $params, NodeInterface $room);

  /**
   * Determines if a start and end date conflicts with a Location's hours.
   *
   * Reservations outside of open hours are considered conflicted.
   *
   * @param array $reservations
   *   An array of existing reservations.
   * @param array $params
   *   An array of reservation parameters.
   * @param \Drupal\node\NodeInterface $room
   *   A Room node.
   *
   * @return bool
   *   Whether a start and end date conflicts with a Location's hours.
   */
  public function hasOpeningHoursConflict(array $reservations, array $params, NodeInterface $room);

  /**
   * Checks if a reservation duration exceeds the room's maximum duration limit.
   *
   * @param array $params
   *   The requested start and end times.
   * @param \Drupal\node\NodeInterface $room
   *   The Room node.
   *
   * @return bool
   *   Whether there is a conflict with the room's maximum duration.
   */
  public function hasMaxDurationConflict(array $params, NodeInterface $room);

  /**
   * Gets the last reservation of a type by a user.
   *
   * @param string $type
   *   The type of reservation.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to check.
   *
   * @return array
   *   An array of Reservation entities.
   */
  public function getReservationsByUser($type, AccountInterface $user);

}
