<?php

/**
 * @file
 * Provides 'legacy' views hooks.
 */

use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\office_hours\Hook\OfficeHoursViewsHooks;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\ViewExecutable;

/**
 * @file
 * Implements views.api.php hooks.
 */

/**
 * Implements hook_field_views_data().
 */
#[LegacyHook]
function office_hours_field_views_data(FieldStorageConfigInterface $field_storage) {
  return \Drupal::service(OfficeHoursViewsHooks::class)->fieldViewsData($field_storage);
}

/**
 * Implements hook_views_query_substitutions().
 */
#[LegacyHook]
function office_hours_views_query_substitutions(ViewExecutable $view) {
  return \Drupal::service(OfficeHoursViewsHooks::class)->viewsQuerySubstitutions($view);
}

/**
 * Implements hook_views_pre_execute().
 */
#[LegacyHook]
function office_hours_views_pre_execute(ViewExecutable $view) {
  return \Drupal::service(OfficeHoursViewsHooks::class)->viewsPreExecute($view);
}

/**
 * Implements hook_views_post_execute().
 */
#[LegacyHook]
function office_hours_views_post_execute(ViewExecutable $view) {
  return \Drupal::service(OfficeHoursViewsHooks::class)->viewsPostExecute($view);
}

/**
 * Implements hook_views_pre_render().
 */
#[LegacyHook]
function office_hours_views_pre_render(ViewExecutable $view) {
  return \Drupal::service(OfficeHoursViewsHooks::class)->viewsPreRender($view);
}

/**
 * Implements hook_views_post_render().
 */
#[LegacyHook]
function office_hours_views_post_render(ViewExecutable $view, array &$output, CachePluginBase $cache) {
  return \Drupal::service(OfficeHoursViewsHooks::class)->viewsPostRender($view, $output, $cache);
}
