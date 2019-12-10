<?php

/**
 * @file
 * Contains event_recurrence.page.inc.
 *
 * Page callback for Event Recurrence entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Event Recurrence templates.
 *
 * Default template: event_recurrence.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_event_recurrence(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
