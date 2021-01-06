<?php

namespace Drupal\intercept_room_reservation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Default implementation of the room reservation provider.
 */
class RoomReservationProvider implements RoomReservationProviderInterface {

  /**
   * The room reservation storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $roomReservationStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new RoomReservationProvider object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->roomReservationStorage = $entity_type_manager->getStorage('room_reservation');
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoomReservations(AccountInterface $account = NULL) {
    $room_reservations = [];
    $room_reservation_ids = $this->getRoomReservationIds($account);
    if ($room_reservation_ids) {
      $room_reservations = $this->roomReservationStorage->loadMultiple($room_reservation_ids);
    }

    return $room_reservations;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoomReservationIds(AccountInterface $account = NULL) {
    $account = $account ?: $this->currentUser;
    if ($account->isAnonymous()) {
      return [];
    }
    $query = $this->roomReservationStorage->getQuery()
      ->condition('field_user', $account->id())
      ->accessCheck(FALSE);
    return $query->execute();
  }

}
