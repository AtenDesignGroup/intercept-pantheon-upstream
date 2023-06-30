<?php

/**
 * @file
 * Contains \Drupal\my_module\Plugin\views\filter\InterceptDateRangeFilter.
 */

namespace Drupal\my_module\Plugin\views\filter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;

/**
 * Filters by a custom date range field based on minimum and maximum dates.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("intercept_date_range_filter")
 */
class InterceptDateRangeFilter extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $field = "{$this->tableAlias}.{$this->realField}";

    // Get the user-supplied minimum and maximum dates.
    $min_date = isset($this->value['min']) ? $this->value['min'] : NULL;
    $max_date = isset($this->value['max']) ? $this->value['max'] : NULL;

    if (!$min_date && !$max_date) {
      // If no date range is provided, do not add any condition.
      return;
    }

    // Convert the user-supplied dates to UTC time zone.
    $min_date = new DrupalDateTime($min_date, new \DateTimeZone(DATETIME_STORAGE_TIMEZONE));
    $min_date->setTimezone(new \DateTimeZone(DATETIME_STORAGE_TIMEZONE));
    $max_date = new DrupalDateTime($max_date, new \DateTimeZone(DATETIME_STORAGE_TIMEZONE));
    $max_date->setTimezone(new \DateTimeZone(DATETIME_STORAGE_TIMEZONE));

    // Add a group of conditions to filter nodes based on the date range.
    $this->query->addWhereGroup('OR');
    $this->query->addWhereGroup('AND');
    $this->query->addWhere($field, $min_date->format(DATETIME_DATETIME_STORAGE_FORMAT), '<=');
    $this->query->addWhere("{$this->tableAlias}.field_date_range_end_value", $min_date->format(DATETIME_DATETIME_STORAGE_FORMAT), '>=');
    $this->query->addWhereGroup('AND');
    $this->query->addWhere($field, $max_date->format(DATETIME_DATETIME_STORAGE_FORMAT), '<=');
    $this->query->addWhere("{$this->tableAlias}.field_date_range_end_value", $max_date->format(DATETIME_DATETIME_STORAGE_FORMAT), '>=');
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function valueForm(&$form, &$form_state) {
    $form['min'] = [
      '#type' => 'date',
      '#title' => $this->t('Minimum date'),
      '#default_value' => isset($this->value['min']) ? $this->value['min'] : NULL,
      '#date_timezone' => 'UTC',
      '#date_time_format' => 'Y-m-d\TH:i:s',
    ];
    $form['max'] = [
      '#type' => 'date',
      '#title' => $this->t('Maximum date'),
      '#default_value' => isset($this->value['max']) ? $this->value['max'] : NULL,
      '#date_timezone' => 'UTC',
      '#date_time_format' => 'Y-m-d\TH:i:s',
    ];
  }

}
