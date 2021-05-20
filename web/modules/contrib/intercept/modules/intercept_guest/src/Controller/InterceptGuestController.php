<?php

namespace Drupal\intercept_guest\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Intercept Guest routes.
 */
class InterceptGuestController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
