<?php

namespace Drupal\intercept_core\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for field level validtation errors.
 *
 * @RenderElement("intercept_field_error_message")
 */
class FieldErrorMessage extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'intercept_field_error_message',
      '#message' => NULL,
    ];
  }

}
