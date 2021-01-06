<?php

namespace Drupal\duration_field\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the granularity_string constraint.
 */
class GranularityStringConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (is_array($items)) {
      foreach ($items as $item) {
        if (!$this->isGranularityString($item)) {
          // The value is not an integer, so a violation, aka error, is applied.
          // The type of violation applied comes from the constraint description
          // in step 1.
          $this->context->addViolation($constraint->notValidGranularityString, ['%value' => (string) $item]);
        }
      }
    }
    else {
      if (!$this->isGranularityString($items)) {
        $this->context->addViolation($constraint->notValidGranularityString, ['%value' => (string) $items]);
      }
    }
  }

  /**
   * Check if a string is a valid granularity string.
   *
   * @param mixed $value
   *   The item to check as a granularity string.
   *
   * @return bool
   *   TRUE if the given value is a valid granularity string. FALSE if it is
   *   not.
   */
  private function isGranularityString($value) {
    if (is_string($value)) {
      return preg_match(GraularityStringInterface::GRANULARITY_STRING_PATTERN, $value);
    }

    return FALSE;
  }

}
