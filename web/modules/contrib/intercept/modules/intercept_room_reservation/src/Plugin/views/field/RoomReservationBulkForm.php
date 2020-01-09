<?php

namespace Drupal\intercept_room_reservation\Plugin\views\field;

use Drupal\views\Plugin\views\field\BulkForm;

/**
 * Defines a room reservation operations bulk form element.
 *
 * @ViewsField("room_reservation_bulk_form")
 */
class RoomReservationBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No reservations selected.');
  }

}
