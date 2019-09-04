<?php

namespace Drupal\intercept_room_reservation;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
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
class RoomReservationStorage extends SqlContentEntityStorage implements RoomReservationStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(RoomReservationInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {room_reservation_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {room_reservation_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(RoomReservationInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {room_reservation_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('room_reservation_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
