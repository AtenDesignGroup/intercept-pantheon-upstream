<?php

namespace Drupal\intercept_room_reservation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a room reservation constraint for only creating reservations in the future.
 *
 * @Constraint(
 *   id = "FutureReservation",
 *   label = @Translation("Reservation must be in the future", context = "Validation"),
 *   type = "entity",
 * )
 */
class FutureReservationConstraint extends Constraint {

  /**
   * The error message to display if the start time is in the past.
   *
   * @var string
   */
  public $errorMessage = 'The reservation must start in the future.';

}
