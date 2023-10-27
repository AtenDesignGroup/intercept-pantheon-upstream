<?php

/**
 * @file
 * Implements third_party_settings hooks provided by the Field UI module.
 */

use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

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
  if (!$items->hasExceptionDays()) {
    return;
  }

  $elements = &$variables['items'];
  // Loop over formatters, edit in place.
  // $elements contains table/status/schema formatters.
  foreach ($elements as $key => &$element) {
    if (is_numeric($key)) {
      // If there is an exception, add an extra row to label the exceptions.
      // Note: may be changed in template_preprocess_office_hours_table().
      $exception_header = [];
      switch ($element['content']['#theme']) {

        case 'office_hours_table':
          $formatter_rows = &$element['content']['#table']['#rows'];
          if ($formatter_rows[OfficeHoursItem::EXCEPTION_DAY] ?? NULL) {
            $exception_header = $formatter_rows[OfficeHoursItem::EXCEPTION_DAY];
            $exception_header['id'] = ['office-hours-exception__title'];
            // Set everything but title to NULL.
            // $exception_header['data']['slots'] = NULL;
            // $exception_header['data']['comments'] = NULL;
          }
          break;

        case 'office_hours':
          $formatter_rows = &$element['content']['#office_hours'];
          if ($formatter_rows[OfficeHoursItem::EXCEPTION_DAY] ?? NULL) {
            $exception_header = $formatter_rows[OfficeHoursItem::EXCEPTION_DAY];
            // Set everything but title to NULL.
            // $exception_header['slots'] = NULL;
            // $exception_header['formatted_slots'] = NULL;
            // $exception_header['index'] = NULL;
            // $exception_header['comments'] = NULL;
          }
          break;

        case 'office_hours_schema':
          // @todo Test/Enhance this formatter for Exception days.
        case 'office_hours_status':
          // @todo Test/Enhance this formatter for Exception days.
        default:
          break;
      }
      if ($exception_header) {
        // Complete the exception header.
        $formatter_rows[OfficeHoursItem::EXCEPTION_DAY] = $exception_header;
      }
    }
  }

}
