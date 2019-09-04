<?php

namespace Drupal\duration_field\Plugin\DataType;

use Drupal\Core\TypedData\PrimitiveInterface;

/**
 * The duration data type.
 *
 * The plain value of an integer is an ISO 8601 Duration string. For setting the
 * value a valid ISO 8601 Duration string must be passed.
 *
 * @ingroup typed_data
 */
interface DurationDataInterface extends PrimitiveInterface {}
