<?php

namespace Drupal\duration_field\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates the iso_8601_string constraint.
 */
class Iso8601StringConstraintValidator extends Iso8601StringConstraintValidatorBase {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (is_array($items)) {
      foreach ($items as $item) {
        if (!$this->isIso8601String($item)) {
          // The value is not a valid ISO 8601 duration string, so a violation,
          // aka error, is applied.
          $this->context->addViolation($constraint->notIso8601, ['%value' => (string) $item]);
        }
      }
    }
    else {
      if (!$this->isIso8601String($items)) {
        $this->context->addViolation($constraint->notIso8601, ['%value' => (string) $items]);
      }
    }
  }

}
