<?php

namespace Drupal\intercept_event\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\intercept_event\EventEvaluationManager;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The form base for Event Evaluations.
 */
abstract class EventEvaluationFormBase extends FormBase {

  /**
   * The Event node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $entity;

  /**
   * The event evaluation manager.
   *
   * @var \Drupal\intercept_event\EventEvaluationManager
   */
  protected $eventEvaluationManager;

  /**
   * Set votingapi entity bundle for new evaluations.
   *
   * @return string
   *   The vote type string.
   */
  abstract protected function getVoteType();

  /**
   * EventEvaluationFormBase constructor.
   */
  public function __construct(EventEvaluationManager $event_evaluation_manager) {
    $this->eventEvaluationManager = $event_evaluation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('intercept_event.evaluation_manager')
    );
  }

  /**
   * Set the current event entity.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The Event node.
   *
   * @return $this
   */
  public function setEntity(NodeInterface $entity) {
    $this->entity = $entity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $evaluation = $this->getEvaluation();
    $form_state->set('evaluation', $evaluation);
    return $form;
  }

  /**
   * Form ajax callback for submit button.
   */
  public function save(&$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Create or load the evaluation for this entity and user.
   *
   * @return \Drupal\intercept_event\EventEvaluation
   *   The Event Evaluation entity.
   */
  protected function getEvaluation() {
    if (!$evaluation = $this->eventEvaluationManager->loadByEntity($this->entity, ['type' => $this->getVoteType()])) {
      $evaluation = $this->eventEvaluationManager->create([
        'entity_id' => $this->entity->id(),
        'type' => $this->getVoteType(),
        'entity_type' => $this->entity->getEntityTypeId(),
      ]);
    }
    return $evaluation;
  }

  /**
   * Create form submit buttons.
   *
   * @return array
   *   The form submit buttons array.
   */
  protected function buildActions() {
    $actions = [];
    $actions['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#ajax' => [
        'callback' => '::save',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Saving feedback...'),
        ],
      ],
    ];
    return $actions;
  }

}
