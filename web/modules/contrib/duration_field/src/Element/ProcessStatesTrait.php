<?php

namespace Drupal\duration_field\Element;

use Drupal\Core\Form\FormHelper;

/**
 * Provides BC layer for invoking drupal_process_states() when needed.
 *
 * The drupal_process_states() procedural function was deprecated in
 * 8.8.x. However, the replacement is only available in 8.8.x. For code to work
 * with both 8.7.x and earlier, and to avoid using this deprecated method in
 * 8.8.x and above, we have to be careful. Since this is shared in both
 * DurationElement and GranularityElement, it lives here in a trait.
 *
 * @todo Remove this when 8.7.x is EOL.
 *
 * @see drupal_process_states()
 * @see \Drupal\Core\Form\FormHelper::processStates()
 * @see https://www.drupal.org/node/3000069
 */
trait ProcessStatesTrait {

  /**
   * Processes a form element for #states support.
   *
   * @param array $element
   *   A render array element having a #states property.
   */
  public static function processStates(array &$element) {
    if (method_exists('\Drupal\Core\Form\FormHelper', 'processStates')) {
      FormHelper::processStates($element);
    }
    else {
      // @noRector
      // @phpstan-ignore-next-line
      drupal_process_states($element);
    }
  }

}
