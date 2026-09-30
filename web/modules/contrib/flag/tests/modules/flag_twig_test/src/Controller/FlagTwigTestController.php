<?php

namespace Drupal\flag_twig_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

/**
 * Defines a test controller for rendering node theme.
 */
class FlagTwigTestController extends ControllerBase {

  /**
   * Render theme.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return array
   *   Build array.
   */
  public function render(NodeInterface $node) {
    return [
      '#theme' => 'flag_test_page',
      '#node' => $node,
    ];
  }

}
