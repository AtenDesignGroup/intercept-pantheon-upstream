<?php

namespace Drupal\duration_field\Normalizer;

use Drupal\duration_field\Plugin\DataType\DateIntervalData;
use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Converts values for the DateIntervalData data type to string.
 *
 * @internal
 */
class DateIntervalDataNormalizer extends NormalizerBase {

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [DateIntervalData::class => TRUE];
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($dateInterval, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    assert($dateInterval instanceof DateIntervalData);
    return $dateInterval->getString();
  }

}
