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
    $this->countValueKey = $this->query->addField(NULL, "(SELECT SUM(value = 'Like') AS count FROM webform_submission_data AS wsd INNER JOIN webform_submission AS ws ON ws.sid = wsd.sid WHERE ws.entity_id = node_field_data.nid AND name = 'how_did_the_event_go' AND ws.webform_id = 'intercept_event_feedback')", $this->countValueKey, []);
  }

  /**
   * Called to add the field to a query.
   */
  public function query() {
    $this->addExpressionField([]);
  }

}
