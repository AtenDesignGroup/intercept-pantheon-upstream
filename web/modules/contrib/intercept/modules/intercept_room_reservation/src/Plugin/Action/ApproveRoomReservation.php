<?php

namespace Drupal\intercept_room_reservation\Plugin\Action;

use Drupal\Core\Field\FieldUpdateActionBase;
use Drupal\intercept_room_reservation\Entity\RoomReservationInterface;

/**
 * Approves a room reservation.
 *
 * @Action(
 *   id = "room_reservation_approve",
 *   label = @Translation("Approve room reservation"),
 *   type = "room_reservation"
 * )
 */
class ApproveRoomReservation extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return ['field_status' => ['value' => 'approved']];
  }

}
