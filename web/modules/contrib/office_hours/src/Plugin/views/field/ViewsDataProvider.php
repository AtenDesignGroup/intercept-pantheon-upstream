<?php

namespace Drupal\office_hours\Plugin\views\field;

use Drupal\field\FieldStorageConfigInterface;

/**
 * Helper class for Office Hours Views fields.
 */
class ViewsDataProvider {

  /**
   * Implements hook_field_views_data().
   *
   * Note: When using full pager on a view, less results might be displayed.
   */
  public static function viewsFieldData(FieldStorageConfigInterface $field_storage, array $data) {
    // Builds upon data from views_field_default_views_data($field_storage);
    $columns = [
      'season' => 'office_hours_season',
      'timeslot' => 'office_hours_timeslot',
      'status' => 'office_hours_status',
    ];

    foreach ($data as $table_name => &$table_data) {
      $field_name = $field_storage->getName();

      // Set the id for the main 'office_hours' ViewsField.
      // $table_data[$field_name]['field']['id'] = 'office_hours';.

      foreach ($columns as $column => $plugin_id) {
        $table_data["{$field_name}_$column"]['field'] = [
          'id' => $plugin_id,
          'field_name' => $field_name,
          'property' => $column,
          'table' => $table_name,
        ];
      }

      if (isset($table_data[$field_name])) {
        $field_label = $table_data[$field_name]['title short'];

        // Extend 'timeslot'.
        // @todo Why is 'timeslot' not filled already, when set in properties?
        $column = 'timeslot';
        $label = $field_label;
        $real_field = $field_name;
        $title = t('@label (@name:@column)',
          ['@label' => $label, '@name' => $field_name, '@column' => $column]);
        $title_short = t('@label:@column',
          ['@label' => $label, '@column' => $column]);

        // @todo Do not take over all field attributes.
        $field_data = &$table_data["{$field_name}_$column"];
        // Use ?? to avoid TypeError: Unsupported operand types: array + null.
        // This may have side effects, since data should exist. @see #3421574.
        $field_data += $table_data[$field_name] ?? [];
        $field_data['title'] = $title;
        $field_data['title short'] = $title_short;
        $field_data['field'] += $table_data[$real_field]['field'] ?? [];
        $field_data['field']['real field'] = $table_data[$real_field]['field']['field_name'];
        // Set a type, to get the 'office_hours' formatter field in Views Field.
        $field_data['field']['type'] = 'office_hours';
        unset($field_data['argument']);
        unset($field_data['filter']);
        unset($field_data['sort']);

        // Extend 'season'.
        $column = 'season';
        $label = $field_label;
        // Better use a higher-level field, perhaps better filtering later.
        // $real_field = 'delta';.
        $real_field = 'entity_id';
        $title = t('@label (@name:@column)',
          ['@label' => $label, '@name' => $field_name, '@column' => $column]
        );
        $title_short = t('@label:@column',
          ['@label' => $label, '@column' => $column]);

        // @todo Do not take over all field attributes.
        $field_data = &$table_data["{$field_name}_$column"];
        // Use ?? to avoid TypeError: Unsupported operand types: array + null.
        // This may have side effects, since data should exist. @see #3421574.
        $field_data += $table_data[$field_name] ?? [];
        $field_data['title'] = $title;
        $field_data['title short'] = $title_short;
        $field_data['field'] += $table_data[$real_field]['field'] ?? [];
        $field_data['field']['real field'] = $real_field;
        // $field_data['field']['default_formatter'] = '@todo';
        unset($field_data['argument']);
        unset($field_data['filter']);
        unset($field_data['sort']);

        // Extend 'Status'.
        // @todo Remove view declaration once#3349739 lands
        // @see https://www.drupal.org/project/drupal/issues/3349739
        $column = 'status';
        $label = $field_label;
        // The 'real field' is referenced in the TimeSlot views field.
        // The 'real field' is referenced in the Status views filter.
        $real_field = $field_name;
        $title = t('@label (@name:@column)',
          ['@label' => $label, '@name' => $field_name, '@column' => $column]
        );
        $title_short = t('@label:@column',
          ['@label' => $label, '@column' => $column]);

        // @todo Do not take over all field attributes.
        $field_data = &$table_data["{$field_name}_$column"];
        // Use ?? to avoid TypeError: Unsupported operand types: array + null.
        // This may have side effects, since data should exist. @see #3421574.
        $field_data += $table_data[$field_name] ?? [];
        $field_data['title'] = $title;
        $field_data['title short'] = $title_short;
        $field_data['help'] = t('The status of the office hours (open/closed/never open).');
        $field_data['field'] += $table_data[$real_field]['field'] ?? [];
        $field_data['field']['real field'] = $real_field;
        // $field_data['field']['default_formatter'] = 'office_hours_status';
        $field_data['field']['handler'] = 'views_handler_field_field';
        // @todo Make the field click-sortable, which cannot be done in query().
        // However, that needs hook_views_pre_render().
        $field_data['field']['click sortable'] = FALSE;
        $field_data['filter']['id'] = 'office_hours_status';
        // $field_data['filter']['field'] = $field_name;
        $field_data['filter']['real field'] = $real_field;
        // $field_data['sort']['id'] = 'numeric';
      }
    }
    return $data;
  }

}
