<?php

namespace Drupal\jsonapi\Normalizer\ImpostorFrom\jsonapi_resources_meta_test;

use Drupal\jsonapi\Normalizer\ResourceObjectNormalizer;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;

/**
 * Adds top-level resource meta, mimicking commerce_api's normalizer.
 *
 * Like commerce_api's EnhancedResourceObjectNormalizer, this decorates
 * serializer.normalizer.resource_object.jsonapi by extending core's normalizer
 * and overriding normalize() — it does not delegate to an inner service. It
 * exists to assert that jsonapi_resources' own decorator does not shadow other
 * decorators of the same service.
 *
 * @see https://www.drupal.org/project/jsonapi_resources/issues/3599417
 */
class MetaShimResourceObjectNormalizer extends ResourceObjectNormalizer {

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    $normalization = parent::normalize($object, $format, $context);
    if (!$normalization instanceof CacheableNormalization) {
      return $normalization;
    }
    $data = $normalization->getNormalization();
    if (!is_array($data)) {
      return $normalization;
    }
    $data['meta'] = ($data['meta'] ?? []) + ['shim' => TRUE];
    return new CacheableNormalization($normalization, $data);
  }

}
