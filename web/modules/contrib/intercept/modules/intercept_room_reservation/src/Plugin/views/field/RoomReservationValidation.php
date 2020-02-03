<?php

namespace Drupal\intercept_room_reservation\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * A handler to provide the computed validation field for room reservations.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("intercept_room_reservation_validation")
 */
class RoomReservationValidation extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $relationship_entities = $values->_relationship_entities;
    $company = '';
    // First check the referenced entity.
    if (isset($relationship_entities['profile'])) {
      $profile = $relationship_entities['profile'];
    }
    else {
      $profile = $values->_entity;
    }

    $type = get_class($profile);
    if ($type === 'Drupal\profile\Entity\Profile') {
      $company = $profile->get('current_company')->getvalue();
    }

    return $company;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // This function exists to override parent query function.
    // Do nothing.
  }
}

}
