<?php

namespace Drupal\intercept_event\Plugin\views\field;

use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;

/**
 * The base views_field_handler for related events.
 */
class EventRelatedEntityCountBase extends NumericField {

  /**
   * The count value property name.
   *
   * @var string
   */
  protected $countValueKey = 'event_count_value';

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    return $values->{$this->countValueKey};
  }

  /**
   * Adds a field to a query.
   *
   * @param array $data
   *   The mapped query data.
   */
  protected function addExpressionField(array $data) {
    $this->countValueKey = $this->query->addField(NULL, "(SELECT SUM(related_field_table.{$data['field_column']})
      FROM {$data['entity_table']} related_entity_table
      LEFT JOIN {$data['join_table']} related_join_table ON related_entity_table.id = related_join_table.entity_id AND related_join_table.deleted = '0'
      LEFT JOIN {$data['field_table']} related_field_table ON related_entity_table.id = related_field_table.entity_id
      WHERE related_join_table.field_event_target_id = node_field_data.nid)", $this->countValueKey, []);
  }

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    if ($this->field == 'event_registration_count') {
      $alias = 'event_count_value';
      $this->query->addOrderBy(NULL, "(SELECT SUM(related_field_table.field_registrants_count)
      FROM event_registration related_entity_table
      LEFT JOIN event_registration__field_event related_join_table ON related_entity_table.id = related_join_table.entity_id AND related_join_table.deleted = '0'
      LEFT JOIN event_registration__field_registrants related_field_table ON related_entity_table.id = related_field_table.entity_id
      WHERE related_join_table.field_event_target_id = node_field_data.nid)", $order, $alias);
    }
    elseif ($this->field == 'event_attendance_count') {
      $alias = 'event_count_value_1';
      $this->query->addOrderBy(NULL, "(SELECT SUM(related_field_table.field_attendees_count)
      FROM event_attendance related_entity_table
      LEFT JOIN event_attendance__field_event related_join_table ON related_entity_table.id = related_join_table.entity_id AND related_join_table.deleted = '0'
      LEFT JOIN event_attendance__field_attendees related_field_table ON related_entity_table.id = related_field_table.entity_id
      WHERE related_join_table.field_event_target_id = node_field_data.nid)", $order, $alias);
    }
  }

}
