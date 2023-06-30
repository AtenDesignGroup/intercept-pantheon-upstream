<?php

namespace Drupal\intercept_room_reservation\Plugin\Block;

use Drupal\views\Views;
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

    // $build['filters'] = $build;

    $view_display = $this->configuration['view_display'];
    if (!empty($view_display)) {
      list($view_id, $display_id) = explode(':', $view_display);
      if (empty($view_id) || empty($display_id)) {
        return;
      }
      $view = Views::getView($view_id);
      if (!empty($view)) {
        $view->setDisplay($display_id);
        $view->initHandlers();
        $view->build();
        if (!empty($view->header)) {
          $build['header'] = [
            '#weight' => 100,
          ];
          foreach ($view->header as $header) {
            $build['header'][] = $header->render();
          }
        }
      }
    }

    return $build;
  }

}
