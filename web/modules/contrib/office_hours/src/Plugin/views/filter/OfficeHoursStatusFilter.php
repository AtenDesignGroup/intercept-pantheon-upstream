<?php

namespace Drupal\office_hours\Plugin\views\filter;

use Drupal\field\FieldStorageConfigInterface;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\ViewExecutable;

/**
 * Filter by open/closed status.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("office_hours_is_open")
 *
 * @see README.md ## USING VIEWS - FILTER CRITERIA
 * @see office_hours.views.inc
 * @see office_hours.schema.yml~views.filter.office_hours_is_open
 * @see https://www.webomelette.com/creating-custom-views-filter-drupal-8
 * @see https://www.drupal.org/docs/drupal-apis/entity-api/dynamicvirtual-field-values-using-computed-field-property-classes
 * @see https://drupal.stackexchange.com/questions/249963/how-to-add-a-custom-views-filter-handler-for-a-specific-field
 */
class OfficeHoursStatusFilter extends ManyToOne {

  /*
   * Duplicate of the @ViewsFilter annotation.
   */
  const VIEWS_FILTER_ID = "office_hours_is_open";
  const ANY = 'all';
  const CLOSED = FALSE;
  const OPEN = TRUE;
  const NEVER = 2;

  /**
   * Implements hook_field_views_data().
   *
   * Note: When using pager on a view, less results might be displayed.
   */
  public static function viewsFieldData(FieldStorageConfigInterface $field_storage, array $data) {
    // The following was called earlier:
    // $data = views_field_default_views_data($field_storage);
    $field_name = $field_storage->getName();
    foreach ($data as $table_name => $table_data) {
      if (isset($data[$table_name][$field_name])) {
        $field_label = $data[$table_name][$field_name]['title short'];

        $data[$table_name][$field_name]['filter'] = [
          'field' => $field_name,
          'table' => $table_name,
          'field_name' => $field_name,
          'id' => static::VIEWS_FILTER_ID,
          'allow_empty' => TRUE,
          'title' => $field_label . ' current open/closed status',
          'title short' => $field_label . ' current open/closed status',
        ];
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    $this->valueOptions = [
      static::ANY => $this->t('Select all'),
      static::OPEN => $this->t('Open now'),
      static::CLOSED => $this->t('Temporarily closed'),
      static::NEVER => $this->t('Permanently closed'),
    ];

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

    $filterValue = $filter->value;
    if (array_key_exists(static::ANY, $filterValue)) {
      return;
    }

    $fieldName = $filter->realField;
    $previous_id = -1;
    /** @var \Drupal\views\ResultRow $value */
    foreach ($view->result as $key => $value) {
      // Remove duplicate rows from the view.
      $id = $value->_entity->id();
      if ($previous_id === $id) {
        unset($view->result[$key]);
        continue;
      }
      $previous_id = $id;

      // Remove filtered rows from the view.
      // Since this is a calculated field, it cannot be done via query().
      /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList $items */
      $items = $value->_entity->$fieldName;
      if (is_null($items) || $items->isEmpty()) {
        if (!array_key_exists(static::NEVER, $filterValue)) {
          unset($view->result[$key]);
        }
        continue;
      }

      $is_open = $items->isOpen();
      if ($is_open && array_key_exists((int) static::OPEN, $filterValue)) {
        continue;
      }
      if ((!$is_open) && array_key_exists((int) static::CLOSED, $filterValue)) {
        continue;
      }
      unset($view->result[$key]);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // The views.inc file is not always loaded. Lazy load here.
    \Drupal::moduleHandler()->loadInclude('office_hours', 'inc', 'office_hours.views');

    // Do not add query details, since this is a computed field,
    // and no SQL is possible.
    // parent::query();
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
   * Implements hook_views_post_execute().
   */
  public static function viewsPostExecute(ViewExecutable $view) {
    // Nothing to do here.
  }

  /**
   * Implements hook_field_views_pre_render().
   */
  public static function viewsPreRender(ViewExecutable $view) {
    if (static::getFilter($view)) {
      self::filter($view);
    }
  }

  /**
   * Implements hook_views_post_render().
   */
  public static function viewsPostRender(ViewExecutable $view, array &$output, CachePluginBase $cache) {
    if (!static::getFilter($view)) {
      return;
    }

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
