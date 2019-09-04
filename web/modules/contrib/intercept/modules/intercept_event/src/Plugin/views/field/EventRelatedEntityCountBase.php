<?php

namespace Drupal\intercept_event\Plugin\views\field;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

class EventRelatedEntityCountBase extends NumericField {

  protected $countValueKey = 'event_count_value';

  public function getValue(ResultRow $values, $field = NULL) {
    return $values->{$this->countValueKey};
  }

  protected function addExpressionField($data) {
    $this->countValueKey = $this->query->addField(NULL, "(SELECT SUM(related_field_table.{$data['field_column']})
      FROM {$data['entity_table']} related_entity_table
      LEFT JOIN {$data['join_table']} related_join_table ON related_entity_table.id = related_join_table.entity_id AND related_join_table.deleted = '0'
      LEFT JOIN {$data['field_table']} related_field_table ON related_entity_table.id = related_field_table.entity_id
      WHERE related_join_table.field_event_target_id = node_field_data.nid)", $this->countValueKey, []);
  }
} 
