<?php

namespace Drupal\intercept_room_reservation\Plugin\views\field;

use Drupal\intercept_room_reservation\Entity\RoomReservationInterface;
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
    $entity = $this->getEntity($values);
    if (!$entity instanceof RoomReservationInterface) {
      return;
    }
    $is_canceled = $entity->get('field_status')->value == 'canceled';
    if (!$is_canceled) {
      $warnings = [];
      $violations = $entity->validationWarnings();
      foreach ($violations->getEntityViolations() as $violation) {
        $warnings[] = $violation->getMessage();
      }
      return [
        '#theme' => 'room_reservation_warnings',
        '#warnings' => $warnings,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {}

}
