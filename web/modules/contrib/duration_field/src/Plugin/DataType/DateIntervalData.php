<?php

namespace Drupal\duration_field\Plugin\DataType;

use DateInterval;
use Drupal\Core\TypedData\PrimitiveBase;

/**
 * Provides the Date Interval data type.
 *
 * This data type is the wrapper for PHP DateInterval objects.
 *
 * @DataType(
 *   id = "php_date_interval",
 *   label = @Translation("Date Interval"),
 * )
 *
 * @see http://php.net/dateinterval
 */
class DateIntervalData extends PrimitiveBase implements DateIntervalInterface {

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $duration_service = \Drupal::service('duration_field.service');

    if (is_a($value, 'DateInterval')) {
      // The value should always be an ISO 8601 Duration String.
      $value = $duration_service->getDurationStringFromDateInterval($value);
    }

    $duration_service->checkDurationInvalid($value);

    parent::setValue($value, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getCastedValue() {
    // The casted value will be a PHP DateInterval object. The value of the
    // object is an ISO 8601 Duration string, used to create the
    // DateInterval object.
    return new DateInterval($this->getString());
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();
    // Add a constraint to ensure that submitted data is valid for a PHP
    // DateInterval object.
    $constraints[] = $constraint_manager->create('php_date_interval', []);

    return $constraints;
  }

}
