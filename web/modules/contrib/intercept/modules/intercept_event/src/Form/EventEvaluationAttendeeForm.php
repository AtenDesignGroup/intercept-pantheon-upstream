<?php

namespace Drupal\intercept_event\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EventEvaluationAttendeeForm extends EventEvaluationFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_evaluation_attendee_form_' . $this->entity->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function getVoteType() {
    return \Drupal\intercept_event\EventEvaluationManager::VOTE_TYPE_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $evaluation = $form_state->get('evaluation');
    $evaluation_vote = $form_state->getValue('evaluation');
    if (!isset($evaluation_vote)) {
      $evaluation_vote = $evaluation->getVote();
    }

    $wrapper_id = 'evaluation-criteria-ajax-wrapper';

    $form['wrapper'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['js-event-evaluation--attendee'],
        'data-event' => [$this->entity->uuid()],
        'data-event-type-primary' => [
          $evaluation->getPrimaryEventType() ? $evaluation->getPrimaryEventType()->uuid() : '',
        ],
      ],
    ];
    $form['evaluation'] = [
      '#title' => $this->t('How\'d the Event Go?'),
      '#type' => 'radios',
      '#options' => [
        0 => $this->t('Dislike'),
        1 => $this->t('Like'),
      ],
      '#default_value' => $evaluation_vote,
    ];

    if ($evaluation->hasCriteria()) {
      $options = $evaluation_vote ? $evaluation->getPositiveCriteriaOptions() : $evaluation->getNegativeCriteriaOptions();
      $form['evaluation']['#ajax'] = [
        'callback' => '::ajaxCallback',
        'wrapper' => $wrapper_id,
      ];
      $form['evaluation_criteria'] = [
        '#title' => $this->t('Tell us Why'),
        '#type' => 'select',
        '#options' => $options,
        '#prefix' => '<div id="' . $wrapper_id . '">', 
        '#suffix' => '</div>',
        '#multiple' => TRUE,
        '#default_value' => $evaluation->getVoteCriteria(),
      ];
    }

    $form['actions'] = $this->buildActions();

    $form['#access'] = $evaluation->access()->isAllowed();

    return $form;
  }

  /**
   * Form ajax handler for repopulating evaluation criteria select.
   */
  public function ajaxCallback(&$form, FormStateInterface $form_state) {
    return $form['evaluation_criteria'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $evaluation = $form_state->get('evaluation');
    $vote = $form_state->getValue('evaluation');
    $vote_criteria = [];
    if ($criteria = $form_state->getValue('evaluation_criteria')) {
      $vote_criteria['taxonomy_term'] = array_values($criteria);
    }
    $evaluation->evaluate($vote, $vote_criteria);
  }

}
