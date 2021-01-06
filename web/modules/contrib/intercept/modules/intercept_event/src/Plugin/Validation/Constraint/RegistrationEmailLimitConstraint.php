<?php

namespace Drupal\intercept_event\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides an event registration per email limit constraint.
 *
 * @Constraint(
 *   id = "RegistrationEmailLimit",
 *   label = @Translation("Registration per event and email limit", context = "Validation"),
 *   type = "event_registration",
 * )
 */
class RegistrationEmailLimitConstraint extends Constraint {

  /**
   * The error message to display after failing validation.
   *
   * @var string
   */
  public $errorMessage = 'A registration already exists for this email.';

  /**
   * The error message to display after failing validation.
   *
   * Personalized if the current user is the registrant.
   *
   * @var string
   */
  public $userMessage = 'You are already registered for this event.';

}
