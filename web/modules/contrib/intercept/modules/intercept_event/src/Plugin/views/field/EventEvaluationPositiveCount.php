<?php

namespace Drupal\intercept_event\Plugin\views\field;

use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;
// use Drupal\intercept_event\EventEvaluationManager;

/**
 * Event evaluation positive count.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("event_evaluation_positive_count")
 */
class EventEvaluationPositiveCount extends NumericField {

  /**
   * The count value property name.
   *
   * @var string
   */
  protected $countValueKey = 'event_evaluation_positive_count';

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

    // SELECT COUNT(value) as count
    // FROM votingapi_vote
    // WHERE entity_id = 3447
    // AND type = 'evaluation'
    // AND value = 1;

    $this->countValueKey = $this->query->addField(NULL, "(SELECT COUNT(value) as count FROM votingapi_vote AS v WHERE v.entity_id = node_field_data.nid AND v.type = 'evaluation' AND v.value = 1)", $this->countValueKey, []);
  }

  /**
   * Called to add the field to a query.
   */
  public function query() {
    // $data = [
    //   'entity_table' => 'event_registration',
    //   'join_table'   => 'event_registration__field_event',
    //   'field_table'  => 'event_registration__field_registrants',
    //   'field_column' => 'field_registrants_count',
    // ];
    $this->addExpressionField([]);
  }

}
