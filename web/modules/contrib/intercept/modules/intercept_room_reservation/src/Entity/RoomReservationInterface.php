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

  /**
   * Sets the Room reservation status to archived.
   *
   * @return \Drupal\intercept_room_reservation\Entity\RoomReservationInterface
   *   The RoomReservation entity.
   */
  public function archive();

  /**
   * Gets validation constraint violations.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   A list of constraint violations. If the list is empty, validation
   *   succeeded.
   */
  public function validationWarnings();

  /**
   * Gets notes associated with this reservation.
   *
   * @return string
   *   The reservation notes.
   */
  public function getNotes();

  /**
   * Sets notes associated with this reservation.
   *
   * @param string $notes
   *   The reservation notes.
   */
  public function setNotes($notes);

}
