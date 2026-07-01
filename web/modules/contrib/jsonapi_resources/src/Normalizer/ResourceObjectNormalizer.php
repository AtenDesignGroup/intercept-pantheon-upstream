<?php

namespace Drupal\jsonapi_resources\Normalizer;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jsonapi\EventSubscriber\ResourceObjectNormalizationCacher;
use Drupal\jsonapi\JsonApiResource\Relationship;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\Normalizer\ResourceObjectNormalizer as BaseResourceObjectNormalizer;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;

/**
 * Custom JSON:API Resource Object normalizer.
 *
 * Resource objects produced by custom resources may contain a custom
 * Relationship that points to another custom resource object rather than being
 * backed by an entity reference field. Core's normalizer cannot serialize those
 * fields — its serializeField() passes the Relationship object straight to
 * CacheableNormalization::permanent(), which rejects nested objects. This
 * normalizer overrides serializeField() to handle them.
 *
 * For every other resource object it delegates to the decorated normalizer
 * rather than replacing it, so that other decorators of
 * serializer.normalizer.resource_object.jsonapi keep running — most importantly
 * commerce_api, which decorates the same service to add top-level resource
 * meta. A plain "extend core and replace" decorator silently shadows any other
 * decorator on the service.
 *
 * @see https://www.drupal.org/project/jsonapi_resources/issues/3599417
 */
class ResourceObjectNormalizer extends BaseResourceObjectNormalizer {

  /**
   * The decorated resource object normalizer.
   *
   * @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface|null
   */
  protected ?NormalizerInterface $inner;

  /**
   * Constructs a new ResourceObjectNormalizer.
   *
   * @param \Drupal\jsonapi\EventSubscriber\ResourceObjectNormalizationCacher $cacher
   *   The normalization cacher.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface|null $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface|null $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface|null $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\Serializer\Normalizer\NormalizerInterface|null $inner
   *   The decorated resource object normalizer.
   */
  public function __construct(ResourceObjectNormalizationCacher $cacher, ?EventDispatcherInterface $event_dispatcher = NULL, ?EntityFieldManagerInterface $entity_field_manager = NULL, ?EntityTypeManagerInterface $entity_type_manager = NULL, ?NormalizerInterface $inner = NULL) {
    parent::__construct($cacher, $event_dispatcher, $entity_field_manager, $entity_type_manager);
    $this->inner = $inner;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    // Resource objects with a custom Relationship field must be normalized
    // here: the decorated normalizer's serializeField() cannot handle those
    // fields and would fatal. Everything else is delegated to the decorated
    // normalizer so that other decorators of this service (e.g. commerce_api
    // adding resource meta) still contribute.
    if ($this->inner === NULL || ($object instanceof ResourceObject && $this->hasRelationshipField($object))) {
      return parent::normalize($object, $format, $context);
    }

    // The jsonapi serializer only injects itself into the outermost decorator,
    // so propagate it to the inner normalizer before delegating.
    if ($this->inner instanceof SerializerAwareInterface) {
      $this->inner->setSerializer($this->serializer);
    }
    return $this->inner->normalize($object, $format, $context);
  }

  /**
   * {@inheritdoc}
   */
  protected function serializeField($field, array $context, $format) {
    // Allow a custom JSON:API Resource to contain a custom Relationship that
    // points to another custom JSON:API Resource, rather than having to be
    // based on an Entity Reference field from a real Entity.
    if ($field instanceof Relationship) {
      $normalized_field = $this->serializer->normalize($field, $format, $context);
      assert($normalized_field instanceof CacheableNormalization);

      return $normalized_field;
    }

    return parent::serializeField($field, $context, $format);
  }

  /**
   * Determines whether a resource object carries a custom Relationship field.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject $object
   *   The resource object.
   *
   * @return bool
   *   TRUE if any field is a Relationship value object.
   */
  protected function hasRelationshipField(ResourceObject $object): bool {
    foreach ($object->getFields() as $field) {
      if ($field instanceof Relationship) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
