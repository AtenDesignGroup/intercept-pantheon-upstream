<?php

namespace Drupal\intercept_equipment\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Equipment reservation entities.
 */
class EquipmentReservationViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['equipment_reservation']['equipment_reservation_bulk_form'] = [
      'title' => $this->t('Equipment reservation operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple equipment reservations.'),
      'field' => [
        'id' => 'equipment_reservation_bulk_form',
      ],
    ];

    return $data;
  }

}
