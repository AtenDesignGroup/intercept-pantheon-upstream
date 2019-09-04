<?php

namespace Drupal\duration_field\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\duration_field\Service\DurationService;

/**
 * The duration data type.
 *
 * The plain value of an integer is an ISO 8601 Duration string. For setting the
 * value a valid ISO 8601 Duration string must be passed.
 *
 * @DataType(
 *   id = "duration",
 *   label = @Translation("Duration")
 * )
 */
class DurationData extends StringData implements DurationDataInterface {

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {

    // Note: this will throw
    // \Drupal\duration_field\Exception\InvalidDurationException if $value is
    // an invalid ISO 8601 Duration string.
    DurationService::checkDurationInvalid($value);

    return parent::setValue($value);
  }

}
