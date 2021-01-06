<?php

namespace Drupal\duration_field\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a valid ISO 8601 duration string.
 *
 * @Constraint(
 *   id = "iso_8601_string",
 *   label = @Translation("ISO 8601 Duration String", context = "Validation"),
 *   type = "string"
 * )
 */
class Iso8601StringConstraint extends Constraint {

  /**
   * The message shown when the value is not a valid ISO 8601 duration string.
   *
   * @var string
   */
  public $notIso8601 = '%value is not a valid ISO 8601 duration string';

}
