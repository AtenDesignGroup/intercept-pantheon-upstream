<?php

namespace Drupal\duration_field\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\StringData;

/**
 * Provides the Iso8601String Typed Data object.
 *
 * This data type is the wrapper for ISO 8601 duration strings.
 *
 * @DataType(
 *   id = "iso_8601_string",
 *   label = @Translation("ISO 8601 Duration String"),
 * )
 *
 * @see https://www.iso.org/iso-8601-date-and-time-format.html
 */
class Iso8601StringData extends StringData implements Iso8601StringInterface {

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();
    $constraints[] = $constraint_manager->create('iso_8601_string', []);

    return $constraints;
  }

}
