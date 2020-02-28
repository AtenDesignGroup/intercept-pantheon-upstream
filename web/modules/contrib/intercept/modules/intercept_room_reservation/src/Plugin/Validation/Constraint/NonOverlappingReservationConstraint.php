<?php

namespace Drupal\intercept_room_reservation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a non-overlapping reservation constraint.
 *
 * @Constraint(
 *   id = "NonOverlappingRoomReservation",
 *   label = @Translation("Make non-overlapping room reservations", context = "Validation"),
 *   type = "entity",
 * )
 */
class NonOverlappingReservationConstraint extends Constraint {

  /**
   * The error message to display after failing validation.
   *
   * @var string
   */
  public $errorMessage = 'This reservation overlaps with another reservation.';

}
