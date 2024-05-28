<?php

declare(strict_types = 1);

namespace Drupal\date_recur\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks the time zone is a recognized zone.
 *
 * @Constraint(
 *   id = \Drupal\date_recur\Plugin\Validation\Constraint\DateRecurTimeZoneConstraint::PLUGIN_ID,
 *   label = @Translation("Valid Time Zone", context = "Validation"),
 *   type = "string"
 * )
 */
class DateRecurTimeZoneConstraint extends Constraint {

  /**
   * The plugin ID.
   */
  public const PLUGIN_ID = 'DateRecurTimeZone';

  /**
   * Violation message for an invalid time zone.
   *
   * @var string
   */
  public string $invalidTimeZone = '%value is not a valid time zone.';

}
