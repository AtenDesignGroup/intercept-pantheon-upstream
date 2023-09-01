<?php

namespace Drupal\intercept_room_reservation;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * RoomReservationCertificationChecker service.
 */
class RoomReservationCertificationChecker {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a RoomReservationCertificationChecker object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Determines whether a specified user is certified for a room.
   *
   * @param int $uid
   *   The user id to be checked for certification for a room.
   * @param int $roomNid
   *   The 'room' type node id to be checked for the user's certification.
   *
   * @return bool
   */
  public function userIsCertified(int $uid, int $roomNid) {
    // Return TRUE if the room doesn't require certification.
    $room = $this->entityTypeManager->getStorage('node')->load($roomNid);
    if (!$room->field_requires_certification->value) {
      return TRUE;
    }

    // See if there is a certification entity for this room and user.
    $query = \Drupal::entityQuery('certification')
      ->accessCheck(FALSE)
      ->condition('field_user', $uid)
      ->condition('field_room', $roomNid);
    $results = $query->execute();
    if (!empty($results)) {
      return TRUE;
    }

    // See if the user with uid $uid can bypass certification constraints.
    /** Drupal\user\Entity\User $account */
    $account = $this->entityTypeManager->getStorage('user')->load($uid);
    if ($account->hasPermission('bypass room certification constraints')) {
      return TRUE;
    }

    return FALSE;
  }

}
