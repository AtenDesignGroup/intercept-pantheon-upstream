<?php

/**
 * @file
 * Provides views data for the office_hours module.
 */

use Drupal\field\FieldStorageConfigInterface;
use Drupal\office_hours\Plugin\views\filter\OfficeHoursStatusFilter;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_field_views_data().
 */
function office_hours_field_views_data(FieldStorageConfigInterface $field_storage) {
  return OfficeHoursStatusFilter::viewsFieldData($field_storage);
}

/**
 * Implements hook_views_query_substitutions().
 */
function office_hours_views_query_substitutions(ViewExecutable $view)
{
  return OfficeHoursStatusFilter::viewsQuerySubstitutions($view);
}

/**
 * Implements hook_views_post_execute().
 */
function office_hours_views_post_execute(ViewExecutable $view)
{
  return OfficeHoursStatusFilter::viewsPostExecute($view);
}

/**
 * Implements hook_views_pre_render().
 */
function office_hours_views_pre_render(ViewExecutable $view)
{
  return OfficeHoursStatusFilter::viewsPreRender($view);
}

/**
 * Implements hook_views_post_render().
 */
function office_hours_views_post_render(ViewExecutable $view, array &$output, CachePluginBase $cache)
{
  return OfficeHoursStatusFilter::viewsPostRender($view, $output, $cache);
}
