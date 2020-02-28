<?php

namespace Drupal\intercept_room_reservation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a room reservation constraint for user limits.
 *
 * @Constraint(
 *   id = "ReservationLimit",
 *   label = @Translation("Reservation Limit", context = "Validation"),
 *   type = "entity",
 * )
 */
class ReservationLimitConstraint extends Constraint {

  /**
   * The error message to display after failing validation.
   *
   * @var string
   */
  public $errorMessage = 'User has exceeded their reservation limit.';

}
