<?php

/**
 * @file
 * Provides views data for the office_hours module.
 *
 * @ingroup views_module_handlers
 */

use Drupal\field\FieldStorageConfigInterface;
use Drupal\office_hours\Plugin\views\field\FieldBase;
use Drupal\office_hours\Plugin\views\filter\OfficeHoursStatusFilter;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_field_views_data().
 */
function office_hours_field_views_data(FieldStorageConfigInterface $field_storage) {
  $data = views_field_default_views_data($field_storage);
  $data = OfficeHoursStatusFilter::viewsFieldData($field_storage, $data);
  $data = FieldBase::viewsFieldData($field_storage, $data);

  return $data;
}

/**
 * Implements hook_views_query_substitutions().
 */
function office_hours_views_query_substitutions(ViewExecutable $view) {
  return OfficeHoursStatusFilter::viewsQuerySubstitutions($view);
}

/**
 * Implements hook_views_post_execute().
 */
function office_hours_views_post_execute(ViewExecutable $view) {
  return OfficeHoursStatusFilter::viewsPostExecute($view);
}

/**
 * Implements hook_views_pre_render().
 */
function office_hours_views_pre_render(ViewExecutable $view) {
  return OfficeHoursStatusFilter::viewsPreRender($view);
}

/**
 * Implements hook_views_post_render().
 */
function office_hours_views_post_render(ViewExecutable $view, array &$output, CachePluginBase $cache) {
  return OfficeHoursStatusFilter::viewsPostRender($view, $output, $cache);
}
