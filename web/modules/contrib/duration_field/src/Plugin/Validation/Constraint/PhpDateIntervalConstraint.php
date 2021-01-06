<?php

namespace Drupal\duration_field\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a valid value for a Date Interval object.
 *
 * Valid values are either PHP DateInterval objects, or an ISO 8601 duration
 * string.
 *
 * @Constraint(
 *   id = "php_date_interval",
 *   label = @Translation("PHP Date Interval", context = "Validation"),
 *   type = "string"
 * )
 */
class PhpDateIntervalConstraint extends Constraint {

  /**
   * The message shown when the value is not a valid PHP DateInterval object.
   *
   * @var string
   */
  public $notDateInterval = '%value is not valid for a PHP DateInterval object';

}
