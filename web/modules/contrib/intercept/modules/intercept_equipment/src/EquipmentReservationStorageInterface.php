<?php

namespace Drupal\intercept_equipment;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\intercept_equipment\Entity\EquipmentReservationInterface;

/**
 * Defines the storage handler class for Equipment reservation entities.
 *
 * This extends the base storage class, adding required special handling for
 * Equipment reservation entities.
 *
 * @ingroup intercept_equipment_reservation
 */
interface EquipmentReservationStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Equipment reservation revision IDs for a reservation.
   *
   * @param \Drupal\intercept_equipment\Entity\EquipmentReservationInterface $entity
   *   The Equipment reservation entity.
   *
   * @return int[]
   *   Equipment reservation revision IDs (in ascending order).
   */
  public function revisionIds(EquipmentReservationInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as reservation author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Equipment reservation revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\intercept_equipment\Entity\EquipmentReservationInterface $entity
   *   The Equipment reservation entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(EquipmentReservationInterface $entity);

  /**
   * Unsets the language for all Equipment reservation with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
