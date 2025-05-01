<?php

namespace Drupal\office_hours\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Contains Theme hooks - class is declared as a service in services.yml file.
 *
 * @todo Remove hook declarations from module file in D11.1.
 * @see https://www.drupal.org/project/office_hours/issues/3505428
 * @see https://drupalize.me/blog/drupal-111-adds-hooks-classes-history-how-and-tutorials-weve-updated
 */
class OfficeHoursThemeHooks {

  /**
   * Implements hook_preprocess_field().
   *
   * Note: Hook preprocess_field must remain procedural (message in D11.1).
   * in Drupal\Core\Hook\HookCollectorPass::checkForProceduralOnlyHooks().
   */
  public function preprocess_field(&$variables, $hook) {
    if ($variables['element']['#field_type'] !== 'office_hours') {
      return;
    }

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
   *
   * Note: Hook preprocess_field must remain procedural (message in D11.1).
   * in Drupal\Core\Hook\HookCollectorPass::checkForProceduralOnlyHooks().
   */
  public function preprocess_office_hours(&$variables) {
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
        // Add caption for season, exception, weekday section headers.
        'caption' => $info['caption'] ?? '',
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
   *
   * Note: Hook preprocess_field must remain procedural (message in D11.1).
   * in Drupal\Core\Hook\HookCollectorPass::checkForProceduralOnlyHooks().
   */
  public function preprocess_office_hours_status(&$variables) {
    // For office-hours-status.html.twig template file.
  }

  /**
   * Implements hook_preprocess_HOOK().
   *
   * Note: Hook preprocess_field must remain procedural (message in D11.1).
   * in Drupal\Core\Hook\HookCollectorPass::checkForProceduralOnlyHooks().
   */
  public function preprocess_office_hours_table(&$variables) {
    // For office-hours-table.html.twig template file.
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() {
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
    ];

    return $themes;
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_office_hours')]
  public function theme_suggestions_office_hours(array $variables) {
    $suggestions = [];

    $hook_name = $variables['hook_name'] ?? 'office_hours';
    /** @var \Drupal\field\Entity\FieldConfig $parent */
    $parent = $variables['parent'];
    if ($parent) {
      $name = $parent->getName();
      $target = $parent->getTargetEntityTypeId();
      $bundle = $parent->getTargetBundle();
      $view_mode = $variables['view_mode'];

      $suggestions[] = "{$hook_name}__{$name}";
      $suggestions[] = "{$hook_name}__{$name}__{$view_mode}";
      $suggestions[] = "{$hook_name}__{$target}__{$name}";
      $suggestions[] = "{$hook_name}__{$target}__{$name}__{$view_mode}";
      $suggestions[] = "{$hook_name}__{$target}__{$name}__{$bundle}";
      $suggestions[] = "{$hook_name}__{$target}__{$name}__{$bundle}__{$view_mode}";
    }

    return $suggestions;
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  public function theme_suggestions_office_hours_status(array $variables) {
    $variables += ['hook_name' => 'office_hours_status'];
    return office_hours_theme_suggestions_office_hours($variables);
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  public function theme_suggestions_office_hours_table(array $variables) {
    $variables += ['hook_name' => 'office_hours_table'];
    return office_hours_theme_suggestions_office_hours($variables);
  }

}
