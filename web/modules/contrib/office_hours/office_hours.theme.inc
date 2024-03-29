<?php

/**
 * @file
 * Implements theme.api.php hooks.
 */

/**
 * Implements hook_theme().
 */
function office_hours_theme() {
  $themes['office_hours'] = [
    'variables' => [
      'parent' => NULL,
      'view_mode' => NULL,
      // Pass filtered office_hours structures to twig theming.
      'office_hours' => [],
      // Pass (unfiltered) office_hours items to twig theming.
      'office_hours_field' => [],
      'is_open' => FALSE,
      'open_text' => NULL,
      'closed_text' => NULL,
      'item_separator' => '<br />',
      'slot_separator' => ', ',
      // Enable dynamic field update in office_hours_status_update.js.
      'attributes' => NULL,
    ],
    'file' => 'office_hours.theme.inc',
  ];
  $themes['office_hours_table'] = [
    'variables' => [
      'parent' => NULL,
      'view_mode' => NULL,
      // Pass filtered office_hours structures to twig theming.
      'office_hours' => [],
      // Pass (unfiltered) office_hours items to twig theming.
      'office_hours_field' => [],
      'table' => [],
      // Enable dynamic field update in office_hours_status_update.js.
      'attributes' => NULL,
    ],
    'file' => 'office_hours.theme.inc',
  ];
  $themes['office_hours_status'] = [
    'variables' => [
      'parent' => NULL,
      'view_mode' => NULL,
      // Pass filtered office_hours structures to twig theming.
      'office_hours' => [],
      // Pass (unfiltered) office_hours items to twig theming.
      'office_hours_field' => [],
      'is_open' => FALSE,
      'open_text' => NULL,
      'closed_text' => NULL,
      // Enable dynamic field update in office_hours_status_update.js.
      'attributes' => NULL,
    ],
    'file' => 'office_hours.theme.inc',
  ];
  $themes['office_hours_schema'] = [
    'variables' => [
      'parent' => NULL,
      'view_mode' => NULL,
      // Pass filtered office_hours structures to twig theming.
      'office_hours' => [],
      // Pass (unfiltered) office_hours items to twig theming.
      'office_hours_field' => [],
    ],
    'file' => 'office_hours.theme.inc',
  ];

  return $themes;
}

/**
 * Implements hook_preprocess_field().
 */
function office_hours_preprocess_field(&$variables, $hook) {
  if ($variables['element']['#field_type'] !== 'office_hours') {
    return;
  }

  // Update variables for exception hours.
  office_hours_exceptions_preprocess_field($variables, $hook);

  $element = $variables['element'];
  // Add view_mode, taking into account some ThirdPartySettings.
  $view_mode = $element['#view_mode'];
  $view_mode = $element['#third_party_settings']['layout_builder']['view_mode'] ?? $view_mode;
  // Note: This could be set in formatter.php with $this->viewMode.
  $delta = 0;
  while (!empty($element[$delta])) {
    $variables['items'][$delta]['content']['#view_mode'] = $view_mode;
    $delta++;
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function office_hours_preprocess_office_hours(&$variables) {
  // For office-hours.html.twig template file.
  $office_hours = $variables['office_hours'];

  // Minimum width for day labels. Adjusted when adding new labels.
  $label_length = 3;
  $values = [];

  foreach ($office_hours as $info) {
    $label = $info['label'];
    $label_length = max($label_length, mb_strlen($label));

    // @todo D10: Superfluous code. Use original values for slots and comments.
    $values[] = [
      'label' => $label,
      'slots' => ['#type' => 'markup', '#markup' => $info['formatted_slots']],
      'comments' => ['#type' => 'markup', '#markup' => $info['comments']],
      'suffix' => $variables['item_separator'],
      // @todo Use $variables['item_separator'] in office-hours.html.twig.
    ];
  }

  $variables['items'] = $values;
  $variables['label_length'] = $label_length;
}

/**
 * Implements hook_preprocess_HOOK().
 */
function office_hours_preprocess_office_hours_status(&$variables) {
  // For office-hours-status.html.twig template file.
}

/**
 * Implements hook_preprocess_HOOK().
 */
function office_hours_preprocess_office_hours_table(&$variables) {
  // For office-hours-table.html.twig template file.
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function office_hours_theme_suggestions_office_hours(array $variables) {
  $suggestions = [];

  $hook_name = $variables['hook_name'] ?? 'office_hours';
  /** @var \Drupal\field\Entity\FieldConfig $parent */
  $parent = $variables['parent'];

  if ($parent) {
    $suggestions[] = $hook_name . '__' . $parent->getName();
    $suggestions[] = $hook_name . '__' . $parent->getName() . '__' . $variables['view_mode'];
    $suggestions[] = $hook_name . '__' . $parent->getTargetEntityTypeId() . '__' . $parent->getName();
    $suggestions[] = $hook_name . '__' . $parent->getTargetEntityTypeId() . '__' . $parent->getName() . '__' . $variables['view_mode'];
    $suggestions[] = $hook_name . '__' . $parent->getTargetEntityTypeId() . '__' . $parent->getName() . '__' . $parent->getTargetBundle();
    $suggestions[] = $hook_name . '__' . $parent->getTargetEntityTypeId() . '__' . $parent->getName() . '__' . $parent->getTargetBundle() . '__' . $variables['view_mode'];
  }

  return $suggestions;
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function office_hours_theme_suggestions_office_hours_table(array $variables) {
  $variables += ['hook_name' => 'office_hours_table'];
  return office_hours_theme_suggestions_office_hours($variables);
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function office_hours_theme_suggestions_office_hours_status(array $variables) {
  $variables += ['hook_name' => 'office_hours_status'];
  return office_hours_theme_suggestions_office_hours($variables);
}
