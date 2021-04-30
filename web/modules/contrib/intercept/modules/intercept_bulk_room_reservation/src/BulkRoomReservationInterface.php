<?php

namespace Drupal\intercept_bulk_room_reservation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a bulk room reservation entity type.
 */
interface BulkRoomReservationInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the bulk room reservation title.
   *
   * @return string
   *   Title of the bulk room reservation.
   */
  public function getTitle();

  /**
   * Sets the bulk room reservation title.
   *
   * @param string $title
   *   The bulk room reservation title.
   *
   * @return \Drupal\intercept_bulk_room_reservation\BulkRoomReservationInterface
   *   The called bulk room reservation entity.
   */
  public function setTitle($title);

  /**
   * Gets the bulk room reservation creation timestamp.
   *
   * @return int
   *   Creation timestamp of the bulk room reservation.
   */
  public function getCreatedTime();

  /**
   * Sets the bulk room reservation creation timestamp.
   *
   * @param int $timestamp
   *   The bulk room reservation creation timestamp.
   *
   * @return \Drupal\intercept_bulk_room_reservation\BulkRoomReservationInterface
   *   The called bulk room reservation entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the bulk room reservation status.
   *
   * @return bool
   *   TRUE if the bulk room reservation is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the bulk room reservation status.
   *
   * @param bool $status
   *   TRUE to enable this bulk room reservation, FALSE to disable.
   *
   * @return \Drupal\intercept_bulk_room_reservation\BulkRoomReservationInterface
   *   The called bulk room reservation entity.
   */
  public function setStatus($status);

}
