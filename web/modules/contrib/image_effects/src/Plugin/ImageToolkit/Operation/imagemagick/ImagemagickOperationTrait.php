<?php

namespace Drupal\image_effects\Plugin\ImageToolkit\Operation\imagemagick;

use Drupal\imagemagick\ImagemagickFormatMapperInterface;

/**
 * Trait for ImageMagick image toolkit operations.
 */
trait ImagemagickOperationTrait {

  /**
   * The format mapper service.
   *
   * @var \Drupal\imagemagick\ImagemagickFormatMapperInterface
   */
  protected $formatMapper;

  /**
   * Returns the format mapper service.
   *
   * @return \Drupal\imagemagick\ImagemagickFormatMapperInterface
   *   The format mapper service.
   */
  protected function getFormatMapper() {
    if (!$this->formatMapper) {
      $this->formatMapper = \Drupal::service(ImagemagickFormatMapperInterface::class);
    }
    return $this->formatMapper;
  }

}
