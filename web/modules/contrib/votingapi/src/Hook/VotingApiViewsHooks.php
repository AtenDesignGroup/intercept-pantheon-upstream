<?php

declare(strict_types=1);

namespace Drupal\votingapi\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Hook implementations used for scheduled execution.
 */
final class VotingApiViewsHooks {

  /**
   * Implements hook_views_query_alter().
   */
  #[Hook('views_query_alter')]
  public function viewsQueryAlter(ViewExecutable $view, QueryPluginBase $query): void {
    // The code below allows sorting by voting results
    // so that no result (when no one voted) means zero.
    $base_table = $view->storage->get('base_table');
    if ($query->getBaseId() != 'views_query') {
      return;
    }
    $vr_aliases = [];
    foreach ($query->tables[$base_table] as $alias => $table_d) {
      $table_info = $query->getTableInfo($alias);
      if ($table_info['table'] == 'votingapi_result') {
        $vr_aliases[$alias] = TRUE;
      }
    }
    $va_fields = [];
    foreach ($query->fields as $f_name => &$f_data) {
      if (isset($vr_aliases[$f_data['table']]) && $f_data['field'] == 'value') {
        $va_fields[$f_name] = $f_data;
      }
    }
    foreach ($va_fields as $va_field) {
      $query->addField(NULL, 'COALESCE(' . $va_field['table'] . '.value, 0)', $va_field['alias'] . '__coa');
    }
    foreach ($query->orderby as &$order) {
      if (isset($va_fields[$order['field']])) {
        $order['field'] = $va_fields[$order['field']]['alias'] . '__coa';
      }
    }
  }

}
