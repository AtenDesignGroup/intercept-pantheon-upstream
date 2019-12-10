<?php

namespace Drupal\intercept_room_reservation\Plugin\Action;

use Drupal\Core\Field\FieldUpdateActionBase;

/**
 * Cancels a room reservation.
 *
 * @Action(
 *   id = "room_reservation_cancel",
 *   label = @Translation("Cancel room reservation"),
 *   type = "room_reservation"
 * )
 */
class CancelRoomReservation extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return ['field_status' => ['value' => 'canceled']];
  }

}
