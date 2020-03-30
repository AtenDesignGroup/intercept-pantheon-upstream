<?php

namespace Drupal\intercept_event\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides an event registration allowed constraint.
 *
 * @Constraint(
 *   id = "RegistrationAllowed",
 *   label = @Translation("Registration is allowed for the event", context = "Validation"),
 *   type = "event_registration",
 * )
 */
class RegistrationAllowedConstraint extends Constraint {

  /**
   * The error message to display after failing validation.
   *
   * @var string
   */
  public $errorMessage = 'This event is not accepting registrations.';

}
