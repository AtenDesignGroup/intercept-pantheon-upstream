<?php

namespace Drupal\intercept_room_reservation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a room reservation constraint for user limits.
 *
 * @Constraint(
 *   id = "Eligibility",
 *   label = @Translation("Eligibility", context = "Validation"),
 *   type = "entity",
 * )
 */
class EligibilityConstraint extends Constraint {

  /**
   * The error message to display after failing validation.
   *
   * return @var string
   */
  public static function getErrorMessage($room) {
    $errorMessage = '';

    // Allow other modules to add an eligibility message using a new hook:
    // hook_intercept_room_reservation_eligibility_message_alter().
    \Drupal::moduleHandler()->invokeAll('intercept_room_reservation_eligibility_message_alter', [&$errorMessage, $room]);

    return $errorMessage;
  }

}
