<?php

namespace Drupal\duration_field\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a valid granularity string.
 *
 * @Constraint(
 *   id = "granularity_string",
 *   label = @Translation("Granularity String", context = "Validation"),
 *   type = "string"
 * )
 */
class GranularityStringConstraint extends Constraint {

  /**
   * The message shown when the value is not a valid granularity string.
   *
   * @var string
   */
  public $notValidGranularityString = '%value is not a valid granularity string';

}
