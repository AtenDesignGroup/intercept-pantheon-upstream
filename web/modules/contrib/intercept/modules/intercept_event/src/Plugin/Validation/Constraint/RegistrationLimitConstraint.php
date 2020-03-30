<?php

namespace Drupal\intercept_event\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides an event registration per user limit constraint.
 *
 * @Constraint(
 *   id = "RegistrationLimit",
 *   label = @Translation("Registration per event and user limit", context = "Validation"),
 *   type = "event_registration",
 * )
 */
class RegistrationLimitConstraint extends Constraint {

  /**
   * The error message to display after failing validation.
   *
   * @var string
   */
  public $errorMessage = 'User is already registered for this event.';

  /**
   * The error message to display after failing validation.
   *
   * Personalized if the current user is the registrant.
   *
   * @var string
   */
  public $userMessage = 'You are already registered for this event.';

}
