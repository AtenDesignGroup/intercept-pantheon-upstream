<?php

namespace Drupal\consumer_image_styles;

use Drupal\consumers\Entity\Consumer;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\image\ImageStyleInterface;

/**
 * Interface for consumer image style providers.
 */
interface ImageStylesProviderInterface {

  /**
   * Load the image styles for a given consumer.
   *
   * @param \Drupal\consumers\Entity\Consumer $consumer
   *   Consumer entity to load image styles for.
   *
   * @return \Drupal\image\Entity\ImageStyle[]
   *   List of image styles keyed by image style id.
   */
  public function loadStyles(Consumer $consumer);

  /**
   * Builds a derivative link based on the image URI and the image style.
   *
   * @param string $uri
   *   The file URI.
   * @param \Drupal\image\ImageStyleInterface $image_style
   *   The image style to apply.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheable_metadata
   *   Cacheable metadata for the normalization.
   *
   * @return array
   *   A structured array that complies with the JSON:API spec for links.
   *
   * @see https://jsonapi.org/format/#document-links
   */
  public function buildDerivativeLink($uri, ImageStyleInterface $image_style, ?CacheableMetadata $cacheable_metadata = NULL);

  /**
   * Checks if an entity is an image.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if the entity is an image.
   * {inheritdoc}
   */
  public function entityIsImage(EntityInterface $entity);

}
