<?php

namespace Drupal\intercept_room_reservation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a MinCapacity constraint.
 *
 * @Constraint(
 *   id = "MinCapacity",
 *   label = @Translation("MinCapacity", context = "Validation"),
 * )
 *
 * To apply this constraint on a particular field implement
 * hook_entity_type_build().
 */
class MinCapacityConstraint extends Constraint {

  /**
   * The error message to display after failing validation.
   *
   * @var string
   */
  public $minCapacityMessage = 'The number of attendees (%value) is below this room\'s minimum capacity (%min).';

}
