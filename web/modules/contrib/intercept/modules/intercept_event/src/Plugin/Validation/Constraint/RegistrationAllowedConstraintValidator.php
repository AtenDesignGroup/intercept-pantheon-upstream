<?php

namespace Drupal\intercept_event\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Entity with the ReservationAllowed constraint.
 */
class RegistrationAllowedConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity)) {
      return;
    }
    $event = $entity->field_event->entity;

    if (!($event->field_must_register->value)) {
      $this->context->addViolation($constraint->errorMessage);
    }
  }

}
