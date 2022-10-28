<?php

namespace Drupal\intercept_room_reservation\Plugin\Block;

use Drupal\views_exposed_filter_blocks\Plugin\Block\ViewsExposedFilterBlocksBlock;

/**
 * Provides a separate views exposed filter block.
 *
 * @Block(
 *   id = "intercept_room_reservation_jsonapi_views_filter_block",
 *   admin_label = @Translation("Intercept JSON:API Views exposed filter block")
 * )
 */
class JsonapiViewsFilterBlockBlock extends ViewsExposedFilterBlocksBlock {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();

    if ($build['#form_id'] == 'views_exposed_form') {
      $view_display = $this->configuration['view_display'];
      if (!empty($view_display)) {
        [$view_id, $display_id] = explode(':', $view_display);
        if (empty($view_id) || empty($display_id)) {
          return $build;
        }

        $build['#attributes']['data-view-id'] = $view_id;
        $build['#attributes']['data-view-display'] = $display_id;
        $build['#attached']['library'][] = 'intercept_room_reservation/jsonApiViewsFilterBlock';
      }
    }

    return $build;
  }

}
