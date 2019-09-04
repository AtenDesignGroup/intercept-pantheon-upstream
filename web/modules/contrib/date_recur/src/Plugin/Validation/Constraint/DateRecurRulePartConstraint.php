<?php

declare(strict_types = 1);

namespace Drupal\date_recur\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Restricts parts in RRULE to a pre-defined subset.
 *
 * @Constraint(
 *   id = "DateRecurRuleParts",
 *   label = @Translation("Frequency and part restriction", context = "Validation"),
 * )
 */
class DateRecurRulePartConstraint extends Constraint {

  public $disallowedPart = '%part is not a permitted part.';

  public $disallowedFrequency = '%frequency is not a permitted frequency.';

  public $incompatiblePart = '%part is incompatible with %frequency.';

}
