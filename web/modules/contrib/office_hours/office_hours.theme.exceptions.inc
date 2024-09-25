<?php

/**
 * @file
 * Implements third_party_settings hooks provided by the Field UI module.
 */

use Drupal\Core\Entity\Entity\EntityViewDisplay;

/* @see File core/modules/field_ui/field_ui.api.php
 *
 * hook_field_formatter_settings_summary_alter
 * 	 Alters the field formatter settings summary.
 * hook_field_formatter_third_party_settings_form
 * 	 Allow modules to add field formatter settings, provided by other modules.
 * hook_field_widget_settings_summary_alter
 *   Alters the field widget settings summary.
 * hook_field_widget_third_party_settings_form
 *   Allow modules to add field widget settings, provided by other modules.
 */

/**
 * Implements hook_preprocess_field().
 */
function office_hours_exceptions_preprocess_field(array &$variables, $hook) {
  if ($variables['element']['#field_type'] !== 'office_hours') {
    return;
  }

  /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items */
  $items = &$variables['element']['#items'];

  if ($items->isEmpty()) {
    return;
  }

  // Check if an exception day exists in the table.
  if (TRUE || !$items->hasExceptionDays()) {
    // The remaining code is of no use at the moment.
    return;
  }

  $element = $variables['element'];
  $entity = $element['#object'];
  $field_name = $element['#field_name'];
  $view_mode = $element['#view_mode'];
  $view_mode = $element['#third_party_settings']['layout_builder']['view_mode'] ?? $view_mode;

  // Check the field formatter settings.
  $entity_display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
  $settings = $entity_display->getComponent($field_name)['settings'] ?? [];
  if (!isset($settings['exceptions']['title'])) {
    \Drupal::messenger()->addMessage(t(
      "Please review the display settings
      for field %field_name in view mode %view_mode, and save them again.",
      [
        '%field_name' => $field_name,
        '%view_mode' => $view_mode,
      ]
    ));
    return;
  }

  $elements = &$variables['items'];
  // Loop over formatters, edit in place.
  // $elements contains table/status/schema formatters.
  foreach ($elements as $key => &$element) {
    if (is_numeric($key)) {
      // If there is an exception, add an extra row to label the exceptions.
      // Note: may be changed in template_preprocess_office_hours_table().
      $exception_header = NULL;
      switch ($element['content']['#theme']) {

        case 'office_hours_table':
          $formatter_rows = &$element['content']['#table']['#rows'];
          break;

        case 'office_hours':
          $formatter_rows = &$element['content']['#office_hours'];
          break;

        case 'office_hours_schema':
          // @todo Test/Enhance this formatter for Exception days.
        case 'office_hours_status':
          // @todo Test/Enhance this formatter for Exception days.
        default:
          break;
      }
    }
  }

}
