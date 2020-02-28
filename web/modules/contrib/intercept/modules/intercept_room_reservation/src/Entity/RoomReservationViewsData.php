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
    $data['room_reservation']['intercept_room_reservation_validation'] = [
      'title' => $this->t('Room reservation validation warnings'),
      'help' => $this->t('Room reservation validation constraint violations.'),
      'field' => [
        'id' => 'intercept_room_reservation_validation',
      ],
    ];

    return $data;
  }

}
