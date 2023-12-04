<?php

namespace Drupal\tally\Plugin\views\filter;

// use Drupal\Core\Database\Database;
use Drupal\views\Plugin\views\filter\NumericFilter;
use Drupal\views\Views;

/**
 * Filter by tally.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("tally_filter")
 */
class TallyFilter extends NumericFilter {

  public function query() {
    // Ensure the main table for this handler is in the query.
    $this->ensureMyTable();
    $operator = $this->operator;
    $value = $this->value;
    $entity_id = $this->definition['entity_id'];
    $entity_type = $this->definition['entity_type'];
    $tableAlias = $this->tableAlias;
    $group = $this->options['group'];

    // JOIN to the attendees table.
    if (!empty($this->value)) {
      $configuration = [
        'table' => 'node__field_attendees',
        'field' => 'entity_id',
        'left_table' => 'node_field_data',
        'left_field' => 'nid',
        'operator' => '=',
      ];
      $join = Views::pluginManager('join')->createInstance('standard', $configuration);
      $this->query->addRelationship('node__field_attendees', $join, 'node');
    }
    // END JOIN

    // Need to do the SUM here.
    if ($operator == 'empty') {
      // Looking for null.
      $this->query->addHavingExpression($group, 'SUM(node__field_attendees.field_attendees_count) IS NULL');
    }
    elseif ($operator == '>=' && $value['value'] == '0') {
      // Looking for positive number data entry.
      $this->query->addHavingExpression($group, 'SUM(node__field_attendees.field_attendees_count) >= 0');
    }
  }

}
