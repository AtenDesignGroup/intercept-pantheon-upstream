<?php

namespace Drupal\intercept_equipment;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
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
class EquipmentReservationStorage extends SqlContentEntityStorage implements EquipmentReservationStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(EquipmentReservationInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {equipment_reservation_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {equipment_reservation_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(EquipmentReservationInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {equipment_reservation_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('equipment_reservation_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
