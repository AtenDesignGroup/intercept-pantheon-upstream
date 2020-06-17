<?php

/**
 * @file
 * Provide Views data for the Intercept module.
 *
 * @ingroup views_module_handlers
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data_alter().
 *
 * Views integration for entity reference fields.
 */
function intercept_core_field_views_data_alter(array &$data, FieldStorageConfigInterface $field_storage) {
  // The code below only deals with the Entity reference field type.
  if ($field_storage->getType() == 'entity_reference') {
    foreach ($data as $table_name => $table_data) {
      // Render filters as select lists.
      foreach ($table_data as $table_field_name => $table_field_data) {
        if (isset($table_field_data['filter']) && $table_field_name != 'delta') {
          $data[$table_name][$table_field_name]['filter']['id'] = 'intercept_entity_reference';
        }
      }
    }
  }
}

/**
 * Implements hook_views_data_alter().
 */
function intercept_core_views_data_alter(array &$data) {
  $data['users_field_data']['customer_edit_link'] = [
    'title' => t('Customer edit link'),
    'help' => t('Edit the user customer profile'),
    'real field' => 'uid',
    'field' => [
      'id' => 'customer_edit_link',
    ],
  ];
}
