<?php

namespace Drupal\office_hours\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\office_hours\Plugin\views\field\ViewsDataProvider;
use Drupal\office_hours\Plugin\views\filter\OfficeHoursStatusFilter;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\ViewExecutable;

/**
 * Contains Views hooks - class is declared as a service in services.yml file.
 *
 * @see https://drupalize.me/blog/drupal-111-adds-hooks-classes-history-how-and-tutorials-weve-updated
 */
class OfficeHoursViewsHooks {

  /**
   * Implements hook_field_views_data().
   */
  #[Hook('field_views_data')]
  public function fieldViewsData(FieldStorageConfigInterface $field_storage) {
    if (version_compare(\Drupal::VERSION, '11.2') >= 0) {
      $data = \Drupal::service('views.field_data_provider')
        ->defaultFieldImplementation($field_storage);
    }
    else {
      $data = views_field_default_views_data($field_storage);
    }
    $data = ViewsDataProvider::viewsFieldData($field_storage, $data);
    return $data;
  }

  /**
   * Implements hook_views_query_substitutions().
   */
  #[Hook('views_query_substitutions')]
  public function viewsQuerySubstitutions(ViewExecutable $view) {
    return OfficeHoursStatusFilter::viewsQuerySubstitutions($view);
  }

  /**
   * Implements hook_views_pre_execute().
   */
  #[Hook('views_pre_execute')]
  public function viewsPreExecute(ViewExecutable $view) {
    return OfficeHoursStatusFilter::viewsPreExecute($view);
  }

  /**
   * Implements hook_views_post_execute().
   */
  #[Hook('views_post_execute')]
  public function viewsPostExecute(ViewExecutable $view) {
    return OfficeHoursStatusFilter::viewsPostExecute($view);
  }

  /**
   * Implements hook_views_pre_render().
   */
  #[Hook('views_pre_render')]
  public function viewsPreRender(ViewExecutable $view) {
    return OfficeHoursStatusFilter::viewsPreRender($view);
  }

  /**
   * Implements hook_views_post_render().
   */
  #[Hook('views_post_render')]
  public function viewsPostRender(ViewExecutable $view, array &$output, CachePluginBase $cache) {
    return OfficeHoursStatusFilter::viewsPostRender($view, $output, $cache);
  }

}
