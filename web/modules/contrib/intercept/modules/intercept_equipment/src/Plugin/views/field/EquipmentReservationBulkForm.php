<?php

namespace Drupal\intercept_equipment\Plugin\views\field;

use Drupal\views\Plugin\views\field\BulkForm;

/**
 * Defines a equipment reservation operations bulk form element.
 *
 * @ViewsField("equipment_reservation_bulk_form")
 */
class EquipmentReservationBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No reservations selected.');
  }

}
