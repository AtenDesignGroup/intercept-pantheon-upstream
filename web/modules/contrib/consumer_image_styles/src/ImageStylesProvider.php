<?php

namespace Drupal\consumer_image_styles;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\consumers\Entity\Consumer;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class ImageStylesProvider.
 *
 * @package Drupal\consumer_image_styles
 */
class ImageStylesProvider implements ImageStylesProviderInterface
{
  private $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   *
   * @param Consumer $consumer
   *   Consumer entity to load image styles for.
   *
   * @return \Drupal\image\Entity\ImageStyle[]
   *   List of image styles keyed by image style id.
   */
  public function loadStyles(Consumer $consumer) {
    $consumer_config = $consumer->get('image_styles')->getValue();
    $image_style_ids = array_map(function ($field_value) {
      return $field_value['target_id'];
    }, $consumer_config);

    // Load image style entities in bulk.
    try {
      $image_styles = $this->entityTypeManager
        ->getStorage('image_style')
        ->loadMultiple($image_style_ids);
    }
    catch(InvalidPluginDefinitionException $e) {
      $image_styles = [];
    }

    return $image_styles;
  }
}
