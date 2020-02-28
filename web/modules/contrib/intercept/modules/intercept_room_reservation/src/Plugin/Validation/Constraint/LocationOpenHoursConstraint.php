<?php

namespace Drupal\intercept_room_reservation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a room reservation constraint for location open hours.
 *
 * @Constraint(
 *   id = "LocationOpenHours",
 *   label = @Translation("Reserve within location open hours", context = "Validation"),
 *   type = "entity",
 * )
 */
class LocationOpenHoursConstraint extends Constraint {

  /**
   * The error message to display after failing validation.
   *
   * @var string
   */
  public $errorMessage = 'This reservation is outside of open hours.';

}
