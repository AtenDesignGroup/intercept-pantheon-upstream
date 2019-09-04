<?php

namespace Drupal\intercept_location\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'InterceptLocationsList' block.
 *
 * @Block(
 *  id = "intercept_locations_list",
 *  admin_label = @Translation("Intercept locations list"),
 * )
 */
class InterceptLocationsList extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#attached']['library'][] = 'intercept_location/locationsList';
    $build['#markup'] = '';
    $build['intercept_locations_list']['#markup'] = '<div id="locationsListRoot"></div>';

    return $build;
  }

}
