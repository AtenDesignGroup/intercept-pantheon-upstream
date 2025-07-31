<?php

namespace Drupal\duration_field\Element;

use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Render\Element\FormElementBase;

/**
 * Base class for the duration element.
 *
 * This class extends the appropriate parent class based on Drupal version.
 */
if (version_compare(\Drupal::VERSION, '10.3.0', '<')) {
  /**
   * Base class for the duration element.
   *
   * @phpstan-ignore-next-line
   */
  abstract class DurationElementBase extends FormElement {
  }
}
else {
  /**
   * Base class for the duration element.
   */
  abstract class DurationElementBase extends FormElementBase {
  }
}
