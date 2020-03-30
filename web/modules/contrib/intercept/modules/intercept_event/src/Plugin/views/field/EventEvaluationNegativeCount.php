<?php

namespace Drupal\intercept_event\Plugin\views\field;

use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;

/**
 * Event evaluation negative count.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("event_evaluation_negative_count")
 */
class EventEvaluationNegativeCount extends NumericField {

  /**
   * The count value property name.
   *
   * @var string
   */
  protected $countValueKey = 'event_evaluation_negative_count';

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
    $this->countValueKey = $this->query->addField(NULL, "(SELECT COUNT(value) as count FROM votingapi_vote AS v WHERE v.entity_id = node_field_data.nid AND v.type = 'evaluation' AND v.value = 0)", $this->countValueKey, []);
  }

  /**
   * Called to add the field to a query.
   */
  public function query() {
    $this->addExpressionField([]);
  }

}
