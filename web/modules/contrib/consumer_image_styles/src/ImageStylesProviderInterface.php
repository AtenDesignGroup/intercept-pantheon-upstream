<?php

namespace Drupal\consumer_image_styles;

use Drupal\consumers\Entity\Consumer;

/**
 * Class ImageStylesProviderInterface.
 *
 * @package Drupal\consumer_image_styles
 */
interface ImageStylesProviderInterface {
  public function loadStyles(Consumer $consumer);
}
