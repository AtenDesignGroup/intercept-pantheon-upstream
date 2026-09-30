<?php

namespace Drupal\flag_count\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for flag_count.
 */
class FlagCountHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public static function theme() {
    return [
      'flag_count' => [
        'variables' => [
          'attributes' => [],
          'title' => NULL,
          'action' => 'flag',
          'flag' => NULL,
          'flaggable' => NULL,
        ],
      ],
    ];
  }

}
