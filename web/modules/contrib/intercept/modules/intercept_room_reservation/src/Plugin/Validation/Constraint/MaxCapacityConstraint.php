<?php

namespace Drupal\intercept_room_reservation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a MaxCapacity constraint.
 *
 * @Constraint(
 *   id = "MaxCapacity",
 *   label = @Translation("MaxCapacity", context = "Validation"),
 * )
 *
 * @DCG
 * To apply this constraint on a particular field implement
 * hook_entity_type_build().
 */
class MaxCapacityConstraint extends Constraint {

  /**
   * The error message to display after failing validation.
   *
   * @var string
   */
  public $maxCapacityMessage = '%value exceeds this room\'s maximum capacity (%max)';

}
