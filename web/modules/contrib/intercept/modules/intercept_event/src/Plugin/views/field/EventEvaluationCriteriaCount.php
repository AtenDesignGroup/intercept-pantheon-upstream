<?php

namespace Drupal\intercept_event\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;

/**
 * Event evaluation criteria count.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("event_evaluation_criteria_count")
 */
class EventEvaluationCriteriaCount extends NumericField {

  /**
   * The count value property name.
   *
   * @var string
   */
  protected $countValueKey = 'event_evaluation_criteria_count';

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    return $values->{$this->countValueKey};
  }

  /**
   * Called to add the field to a query.
   */
  public function query() {
    $data = [
      'term_id' => $this->options['term_id'],
    ];
    $this->addExpressionField($data);
  }

  /**
   * Adds a field to a query.
   *
   * @param array $data
   *   The mapped query data.
   */
  protected function addExpressionField(array $data) {
    $this->countValueKey = $this->query->addField(NULL, "(SELECT COUNT(vote_criteria) as count FROM votingapi_vote WHERE entity_id = node_field_data.nid AND type = 'evaluation' AND vote_criteria LIKE '%:{$data['term_id']}%')", $this->countValueKey, []);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['term_id'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $options = [];
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree('evaluation_criteria');
    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }
    $form['term_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Evaluation Criterion'),
      '#default_value' => $this->options['term_id'],
      '#options' => $options,
      '#required' => TRUE,
    ];

    parent::buildOptionsForm($form, $form_state);
  }

}
