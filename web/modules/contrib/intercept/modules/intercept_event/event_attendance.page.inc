<?php

/**
 * @file
 * Contains event_attendance.page.inc.
 *
 * Page callback for Event Attendance entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Event Attendance templates.
 *
 * Default template: event_attendance.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_event_attendance(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
