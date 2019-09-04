<?php

/**
 * @file
 * Provides views data for the tally.module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_views_data_alter().
 */
function tally_field_views_data_alter(array &$data, FieldStorageConfigInterface $field_storage) {
  if ($field_storage->getType() == 'tally_reference') {
    foreach ($data as $table_name => $table_data) {
      foreach ($table_data as $field_name => $field_data) {
        if (isset($field_data['field']) && $field_data['field']['id'] == 'field') {
          $data[$table_name][$field_name]['field']['id'] = 'tally_field';
        }
      }
    }
  }
}
