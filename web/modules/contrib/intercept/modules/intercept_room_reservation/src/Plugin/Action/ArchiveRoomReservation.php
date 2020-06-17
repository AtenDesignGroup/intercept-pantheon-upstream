<?php

namespace Drupal\intercept_room_reservation\Plugin\Action;

use Drupal\Core\Field\FieldUpdateActionBase;

/**
 * Archives a room reservation.
 *
 * @Action(
 *   id = "room_reservation_archive",
 *   label = @Translation("Archive room reservation"),
 *   type = "room_reservation"
 * )
 */
class ArchiveRoomReservation extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return ['field_status' => ['value' => 'archived']];
  }

}
