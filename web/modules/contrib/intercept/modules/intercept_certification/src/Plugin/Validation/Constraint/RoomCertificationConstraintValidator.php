<?php

namespace Drupal\intercept_certification\Plugin\Validation\Constraint;

use Drupal\intercept_room_reservation\Plugin\Validation\Constraint\StaffRoomPermissionsConstraintValidator;
use Symfony\Component\Validator\Constraint;

/**
 * Validates the user has is certified for this room.
 */
class RoomCertificationConstraintValidator extends StaffRoomPermissionsConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity)) {
      return;
    }

    if ($entity->field_room->isEmpty()) {
      return;
    }

    $room = $entity->field_room->entity;
    $user = $this->currentUser;

    if ($room->field_requires_certification->value === '1' && !$user->hasPermission('bypass room certification constraints')) {
      // Query to see if this user is certified for this room.
      $query = \Drupal::entityQuery('certification')
        ->accessCheck(FALSE)
        ->condition('field_room', $room->id())
        ->condition('status', '1')
        ->condition('field_user', $user->id());
      $entity_ids = $query->execute();
      $room_details = '  <a id="view-room-details" data-room-id="' . $room->id() . '" href="/room/' . $room->id() . '">View Room Details</a>';
      if (empty($entity_ids)) {
        $this->context->addViolation($constraint->certErrorMessage . $room_details . ' for more information.');
        return;
      }
      else {
        return;
      }
    }

    if ($room->field_staff_use_only->value === '1' && !$this->currentUser->hasPermission('view staff use room reservation')) {
      $this->context->addViolation($constraint->staffErrorMessage);
    }

    return;
  }

}
