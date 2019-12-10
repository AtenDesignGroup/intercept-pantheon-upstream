<?php

namespace Drupal\intercept_room_reservation\Plugin\Action;

use Drupal\Core\Field\FieldUpdateActionBase;

/**
 * Denies a room reservation.
 *
 * @Action(
 *   id = "room_reservation_deny",
 *   label = @Translation("Deny room reservation"),
 *   type = "room_reservation"
 * )
 */
class DenyRoomReservation extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return ['field_status' => ['value' => 'denied']];
  }

}
