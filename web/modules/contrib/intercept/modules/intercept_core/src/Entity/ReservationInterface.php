<?php

namespace Drupal\intercept_core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Room reservation entities.
 *
 * @ingroup intercept_room_reservation
 */
interface ReservationInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Room reservation creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Room reservation.
   */
  public function getCreatedTime();

  /**
   * Sets the Room reservation creation timestamp.
   *
   * @param int $timestamp
   *   The Room reservation creation timestamp.
   *
   * @return \Drupal\intercept_room_reservation\Entity\RoomReservationInterface
   *   The called Room reservation entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Room reservation published status indicator.
   *
   * Unpublished Room reservation are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Room reservation is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Room reservation.
   *
   * @param bool $published
   *   TRUE to set this Room reservation to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\intercept_room_reservation\Entity\RoomReservationInterface
   *   The called Room reservation entity.
   */
  public function setPublished($published);

  /**
   * Gets the Room reservation revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Room reservation revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\intercept_room_reservation\Entity\RoomReservationInterface
   *   The called Room reservation entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Room reservation revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Room reservation revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\intercept_room_reservation\Entity\RoomReservationInterface
   *   The called Room reservation entity.
   */
  public function setRevisionUserId($uid);

  public function getStartDate();

  public function getEndDate();

  public function getDateRange($timezone = 'UTC');

  /**
   * Get the user the registration is for.
   *
   * @return AccountInterface|bool
   */
  public function getRegistrant();
}
