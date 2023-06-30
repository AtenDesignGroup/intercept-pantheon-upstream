<?php

/**
 * @file
 * Describes hooks provided by the Views Filters Summary module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter a filter's summary info definition.
 *
 * Allows developers to adjust how a given Views exposed
 * filter summary is displayed.
 *
 * @param array $info
 *   An array of definition properties.
 * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
 *   The view filter plugin instance.
 *
 * @see \Drupal\views_filters_summary\Plugin\views\area::buildFilterDefinition()
 */
function hook_views_filters_summary_info_alter(array &$info, \Drupal\views\Plugin\views\filter\FilterPluginBase $filter) {
  if ($filter->getPluginId() === 'datetime' && $time = strtotime($filter->value['value'])) {
    $info['value'] = [
      [
        'id' => 0,
        'raw' => $filter->value['value'],
        'value' => \Drupal::service('date.formatter')->format(
          $time,
          'custom',
          'F d, Y'
        ),
      ],
    ];
  }
}

/**
 * @} End of "addtogroup hooks".
 */
