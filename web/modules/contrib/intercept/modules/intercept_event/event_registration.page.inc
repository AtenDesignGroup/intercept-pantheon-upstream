<?php

/**
 * @file
 * Contains event_registration.page.inc.
 *
 * Page callback for Event Registration entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Event Registration templates.
 *
 * Default template: event_registration.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_event_registration(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
