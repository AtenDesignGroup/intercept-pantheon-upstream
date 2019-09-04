<?php

namespace Drupal\intercept_room_reservation\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Room reservation entities.
 */
class RoomReservationViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['room_reservation']['room_reservation_bulk_form'] = [
      'title' => $this->t('Room reservation operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple room reservations.'),
      'field' => [
        'id' => 'room_reservation_bulk_form',
      ],
    ];

    return $data;
  }

}
