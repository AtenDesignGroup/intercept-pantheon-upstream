<?php

namespace Drupal\flag_twig_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for flag_twig_test.
 */
class FlagTwigTestHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public static function theme($existing, $type, $theme, $path) {
    return [
      'flag_test_page' => [
        'variables' => [
          'node' => NULL,
        ],
        'template' => 'flag-test-page',
      ],
    ];
  }

}
