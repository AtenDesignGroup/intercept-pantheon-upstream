<?php

namespace Drupal\office_hours\Plugin\views\filter;

use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursStatus;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\ViewExecutable;

/**
 * Views Filter by open/closed status.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("office_hours_status")
 *
 * @see README.md ## USING VIEWS - FILTER CRITERIA
 * @see office_hours.views.inc
 * @see office_hours.schema.yml~views.filter.office_hours_status
 * @see https://www.webomelette.com/creating-custom-views-filter-drupal-8
 * @see https://www.drupal.org/docs/drupal-apis/entity-api/dynamicvirtual-field-values-using-computed-field-property-classes
 * @see https://drupal.stackexchange.com/questions/249963/how-to-add-a-custom-views-filter-handler-for-a-specific-field
 * @see https://drupal.stackexchange.com/questions/291236/creating-a-custom-field-with-dynamic-virtual-computed-property-value
 */
class OfficeHoursStatusFilter extends ManyToOne {

  /*
   * Duplicate of the above @ViewsFilter annotation.
   */
  public const VIEWS_FILTER_ID = "office_hours_status";

  /**
   * {@inheritdoc}
   *
   * The formatter settings are taken from the main office_hours field.
   * This field is required and its name is defined in 'real field'.
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $field_name = $this->configuration['real field'];
      $formatter_settings = $this->view->field[$field_name]->options['settings'] ?? [];
      $this->valueOptions = OfficeHoursStatus::getOptions(NULL, $formatter_settings);
    }
    return $this->valueOptions;
  }

  /**
   * Internal function to determine if view is relevant for this filter.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to analyze.
   *
   * @return \Drupal\views\Plugin\views\filter\FilterPluginBase|null
   *   The fetched filter object, if found.
   */
  protected static function getFilter(ViewExecutable $view) {
    if ($view->filter) {
      foreach ($view->filter as $filter) {
        if ($filter->getPluginId() == static::VIEWS_FILTER_ID) {
          return $filter;
        }
      }
    }
    return NULL;
  }

  /**
   * Internal function to filter results. Can be called from any hook.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to filter upon. Rows will be removed from $view->result array.
   */
  protected static function filter(ViewExecutable $view) {
    $filter = static::getFilter($view);
    if (!$filter) {
      return;
    }

    // @todo Remove duplicate rows from the view.
    $previous_id = -1;
    foreach ($view->result as $key => $value) {
      $id = $value->_entity->id();
      if ($previous_id === $id) {
        // Do not remove, for sometimes we want to show day or season per row.
        // unset($view->result[$key]);
      }
      $previous_id = $id;
    }

    $filter_value = $filter->value;
    if (empty($filter_value)) {
      return;
    }
    if (in_array('all', $filter_value)) {
      return;
    }

    // Remove filtered rows from the view.
    // Since this is a calculated field, it cannot be done via query().
    $field_name = $filter->realField;
    foreach ($view->result as $key => $value) {
      try {
        $items = $value->_entity->$field_name;
        $status = $items->{'status'} ?? OfficeHoursStatus::NEVER;
        if (!in_array($status, $filter_value)) {
          unset($view->result[$key]);
        }
      }
      catch (\Throwable $th) {
        // @todo Error sometimes occurs, but not reproducible.
        $id = $value->_entity->id();
        $entity = $value->_entity;
        $field = $value->_entity->$field_name;
        throw $th;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
  }

  /**
   * Implements hook_views_query_substitutions().
   *
   * Allow replacement of current data so we can cache these queries.
   */
  public static function viewsQuerySubstitutions(ViewExecutable $view) {
    // This is not used, only for later reference.
    return ['***OFFICE_HOURS_REQUEST_TIME***' => \Drupal::time()->getRequestTime()];
  }

  /**
   * Implements hook_views_pre_execute().
   */
  public static function viewsPreExecute(ViewExecutable $view) {
    // Nothing to do here.
  }

  /**
   * Implements hook_views_post_execute().
   */
  public static function viewsPostExecute(ViewExecutable $view) {
    if (static::getFilter($view)) {
      self::filter($view);
    }
  }

  /**
   * Implements hook_views_pre_render().
   */
  public static function viewsPreRender(ViewExecutable $view) {
    // Nothing to do here.
  }

  /**
   * Implements hook_views_post_render().
   */
  public static function viewsPostRender(ViewExecutable $view, array &$output, CachePluginBase $cache) {
    // @todo Improve time-based caching (is_open/closed status),
    // setting $output['#cache']['max-age'] from $items->getCacheMaxAge().
    // $cache->options['results_lifespan'] = 0;
    // $cache->options['output_lifespan'] = 0;
    // $output['#cache']['max-age'] = 1;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $max_age = parent::getCacheMaxAge();
    // $max_age = $this->getDefaultCacheMaxAge();
    // $max_age = Cache::mergeMaxAges($max_age, $this->view->getQuery()
    // ->getCacheMaxAge());
    return $max_age;
  }

  /**
   * {@inheritdoc}
   *
   * @see OfficeHoursCacheHelper~getCacheContexts()
   * @see OfficeHoursCacheHelper~getCacheTags()
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    // This filter depends on ....
    // $contexts[] = 'office_hours:field.status'; .
    return $contexts;
  }

}
