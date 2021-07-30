<?php

/**
 * @file
 * Contains certification.page.inc.
 *
 * Page callback for Certification entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Certification templates.
 *
 * Default template: certification.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_certification(array &$variables) {
  // Fetch Certification Entity Object.
  $certification = $variables['elements']['#certification'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
