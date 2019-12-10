<?php

namespace Drupal\intercept_room_reservation;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\intercept_room_reservation\Entity\RoomReservationInterface;

/**
 * Defines the storage handler class for Room reservation entities.
 *
 * This extends the base storage class, adding required special handling for
 * Room reservation entities.
 *
 * @ingroup intercept_room_reservation
 */
interface RoomReservationStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Reservation revision IDs for a specific Room reservation.
   *
   * @param \Drupal\intercept_room_reservation\Entity\RoomReservationInterface $entity
   *   The Room reservation entity.
   *
   * @return int[]
   *   Room reservation revision IDs (in ascending order).
   */
  public function revisionIds(RoomReservationInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Room reservation author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Room reservation revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\intercept_room_reservation\Entity\RoomReservationInterface $entity
   *   The Room reservation entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(RoomReservationInterface $entity);

  /**
   * Unsets the language for all Room reservation with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
