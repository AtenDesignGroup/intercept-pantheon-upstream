<?php

namespace Drupal\intercept_equipment\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Equipment reservation entities.
 *
 * @ingroup intercept_equipment_reservation
 */
interface EquipmentReservationInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Equipment reservation creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Equipment reservation.
   */
  public function getCreatedTime();

  /**
   * Sets the Equipment reservation creation timestamp.
   *
   * @param int $timestamp
   *   The Equipment reservation creation timestamp.
   *
   * @return \Drupal\intercept_equipment\Entity\EquipmentReservationInterface
   *   The called Equipment reservation entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Equipment reservation published status indicator.
   *
   * Unpublished Equipment reservation are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Equipment reservation is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Equipment reservation.
   *
   * @param bool $published
   *   TRUE to set this entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\intercept_equipment\Entity\EquipmentReservationInterface
   *   The called Equipment reservation entity.
   */
  public function setPublished($published);

  /**
   * Gets the Equipment reservation revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Equipment reservation revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\intercept_equipment\Entity\EquipmentReservationInterface
   *   The called Equipment reservation entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Equipment reservation revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Equipment reservation revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\intercept_equipment\Entity\EquipmentReservationInterface
   *   The called Equipment reservation entity.
   */
  public function setRevisionUserId($uid);

}
