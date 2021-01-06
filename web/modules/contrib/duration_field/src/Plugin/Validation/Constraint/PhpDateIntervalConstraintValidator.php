<?php

namespace Drupal\duration_field\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates the php_date_interval constraint.
 */
class PhpDateIntervalConstraintValidator extends Iso8601StringConstraintValidatorBase {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {

    if (is_array($items)) {
      foreach ($items as $item) {
        if (!$this->isPhpDateIntervalObject($item) && !$this->isIso8601String($item)) {
          // The value is not an PHP Date Interval, so a violation, aka error,
          // is applied.
          $this->context->addViolation($constraint->notDateInterval, ['%value' => (string) $item]);
        }
      }
    }
    else {
      if (!$this->isPhpDateIntervalObject($items) && !$this->isIso8601String($items)) {
        // The value is not an PHP Date Interval, so a violation, aka error,
        // is applied.
        $this->context->addViolation($constraint->notDateInterval, ['%value' => (string) $items]);
      }
    }
  }

  /**
   * Test if the given value is a valid ISO 8601 duration string.
   *
   * @param mixed $value
   *   The item to check as an ISO 8601 duration string.
   *
   * @return bool
   *   TRUE if the value is a valid ISO 8601 Duration string. Otherwise FALSE.
   */
  protected function isPhpDateIntervalObject($value) {
    return is_object($value) && is_a($value, 'DateInterval');
  }

}
