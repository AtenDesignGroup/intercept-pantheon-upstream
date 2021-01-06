<?php

namespace Drupal\intercept_core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
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
   * Set the type of reservation, either room or equipment.
   *
   * @return string
   *   The type of reservation.
   */
  public static function reservationType();

  /**
   * Gets the parent entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The parent entity, or NULL.
   */
  public function getParentEntity();

  /**
   * Gets the parent ID.
   *
   * @return int|null
   *   The parent entity ID, or NULL.
   */
  public function getParentId();

  /**
   * Sets the parent entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $parent
   *   The parent entity.
   *
   * @return $this
   */
  public function setParentEntity(EntityInterface $parent);

  /**
   * Gets an array of start and end dates.
   *
   * @param string $timezone
   *   String timezone name. Defaults to UTC.
   *
   * @return array
   *   The array representation of the reservation date range.
   */
  public function getDateRange($timezone = 'UTC');

  /**
   * Gets the start date for a reservation.
   *
   * @return \Drupal\Component\Datetime\DateTimePlus
   *   The start date.
   */
  public function getStartDate();

  /**
   * Gets the end date for a reservation.
   *
   * @return \Drupal\Component\Datetime\DateTimePlus
   *   The end date.
   */
  public function getEndDate();

  /**
   * Gets the duration in minutes between the start and end date.
   *
   * @return int
   *   The duration in minutes.
   */
  public function getDuration();

  /**
   * Gets the DateInterval difference between the start and end date.
   *
   * @return \DateInterval|string
   *   A DateInterval object, or an empty string.
   */
  public function getInterval();

  /**
   * Gets the location entity associated with the reservation.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The location entity.
   */
  public function getLocation();

  /**
   * Gets the list of available operations based on the current status.
   *
   * @return array
   *   The available operations.
   */
  public function getStatusOperations();

  /**
   * Gets the original reservation status.
   *
   * @return string|bool
   *   The original reservation status, or FALSE.
   */
  public function getOriginalStatus();

  /**
   * Gets the current reservation status.
   *
   * @return string|bool
   *   The current reservation status, or FALSE.
   */
  public function getNewStatus();

  /**
   * Gets the current reservation status.
   *
   * @return string|bool
   *   The current reservation status, or FALSE.
   */
  public function getStatus();

  /**
   * Whether the reservation status is changing on save.
   *
   * @return bool
   *   Whether the reservation status is changing on save.
   */
  public function statusHasChanged();

  /**
   * A string for the location node associated with this reservation.
   *
   * @TODO: Change this to locationString();
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The location node title string.
   */
  public function location();

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
   *   TRUE to set this entity to published, FALSE to set it to unpublished.
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

  /**
   * Get the user the reservation is for.
   *
   * @return \Drupal\Core\Session\AccountInterface|bool
   *   The reservation user entity, or FALSE.
   */
  public function getReservor();

  /**
   * Get the available reservation operations for the current user.
   *
   * @return array|bool
   *   An associative array of operation link data for this entity, keyed by
   *   operation name, containing the following key-value pairs:
   *   - title: The localized title of the operation.
   *   - url: An instance of \Drupal\Core\Url for the operation URL.
   *   - weight: The weight of this operation.
   *   - attributes: The link attributes for the operation URL.
   */
  public function getOperations();

  /**
   * Get the reservation status change operations for the current user.
   *
   * @return array|bool
   *   An associative array of operation link data for this entity, keyed by
   *   operation name, containing the following key-value pairs:
   *   - title: The localized title of the operation.
   *   - url: An instance of \Drupal\Core\Url for the operation URL.
   *   - weight: The weight of this operation.
   *   - attributes: The link attributes for the operation URL.
   */
  public function getStatusChangeOperations();

}
