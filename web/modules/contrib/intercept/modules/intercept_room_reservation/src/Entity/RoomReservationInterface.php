<?php

namespace Drupal\intercept_room_reservation\Entity;

use Drupal\intercept_core\Entity\ReservationInterface;

/**
 * Provides an interface for defining Room reservation entities.
 *
 * @ingroup intercept_room_reservation
 */
interface RoomReservationInterface extends ReservationInterface {

  /**
   * Sets the Room reservation status to canceled.
   *
   * @return \Drupal\intercept_room_reservation\Entity\RoomReservationInterface
   *   The RoomReservation entity.
   */
  public function cancel();

  /**
   * Sets the Room reservation status to approved.
   *
   * @return \Drupal\intercept_room_reservation\Entity\RoomReservationInterface
   *   The RoomReservation entity.
   */
  public function approve();

  /**
   * Sets the Room reservation status to requested.
   *
   * @return \Drupal\intercept_room_reservation\Entity\RoomReservationInterface
   *   The RoomReservation entity.
   */
  public function request();

  /**
   * Sets the Room reservation status to denied.
   *
   * @return \Drupal\intercept_room_reservation\Entity\RoomReservationInterface
   *   The RoomReservation entity.
   */
  public function decline();

  /**
   * Sets the Room reservation status to denied.
   *
   * @return \Drupal\intercept_room_reservation\Entity\RoomReservationInterface
   *   The RoomReservation entity.
   */
  public function deny();

}
