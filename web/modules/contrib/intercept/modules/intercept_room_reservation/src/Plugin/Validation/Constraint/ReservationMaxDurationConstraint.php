<?php

namespace Drupal\intercept_room_reservation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a room reservation constraint for maximum duration.
 *
 * @Constraint(
 *   id = "ReservationMaxDuration",
 *   label = @Translation("Reserve below or at maximum duration", context = "Validation"),
 *   type = "entity",
 * )
 */
class ReservationMaxDurationConstraint extends Constraint {

  /**
   * The error message to display after failing validation.
   *
   * @var string
   */
  public $errorMessage = "This reservation exceeds the room's maximum duration.";

}
