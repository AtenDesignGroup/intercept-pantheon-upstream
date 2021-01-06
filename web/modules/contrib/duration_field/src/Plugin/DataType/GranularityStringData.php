<?php

namespace Drupal\duration_field\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\StringData;

/**
 * Provides the Granularity String typed data object.
 *
 * A granularity string contains any or all of the following keys:
 *   - y (years)
 *   - m (months)
 *   - d (days)
 *   - h (hours)
 *   - i (minutes)
 *   - s (seconds)
 *
 * Keys are separated by colons. The presence of a key means that degree of
 * granularity is relevant. For example, a granularity string of y:m:d
 * has keys for year, month and day, and therefore any time elements using that
 * granularity would collect values for year, month and day. A granularity of
 * y:s would indicate a granularity of years and seconds. An empty string means
 * no granularity. Full granularity is y:m:d:h:i:s.
 *
 * @DataType(
 *   id = "granularity_string",
 *   label = @Translation("Granularity String"),
 * )
 */
class GranularityStringData extends StringData implements GranularityStringInterface {

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();
    // Add constraint to ensure that submitted data is a granularity string.
    $constraints[] = $constraint_manager->create('granularity_string', []);

    return $constraints;
  }

}
