<?php

namespace Drupal\duration_field\Plugin\Validation\Constraint;

use Drupal\duration_field\Plugin\DataType\Iso8601StringInterface;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Provides a base for validating ISO 8601 String constraints.
 */
abstract class Iso8601StringConstraintValidatorBase extends ConstraintValidator {

  /**
   * Test if a string is a valid ISO 8601 duration string.
   *
   * @param mixed $value
   *   The item to check as an ISO 8601 duration string.
   *
   * @return bool
   *   TRUE if the given value is a valid ISO 8601 duration string. FALSE if it
   *   is not.
   */
  protected function isIso8601String($value) {
    if (is_string($value)) {
      return preg_match(Iso8601StringInterface::DURATION_STRING_PATTERN, $value);
    }

    return FALSE;
  }

}
