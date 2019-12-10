<?php

namespace Drupal\intercept_event\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\intercept_event\EventEvaluationManager;

/**
 * The Event Evaluation Staff form.
 */
class EventEvaluationStaffForm extends EventEvaluationFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_evaluation_staff_form_' . $this->entity->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function getVoteType() {
    return EventEvaluationManager::VOTE_TYPE_STAFF_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $evaluation = $form_state->get('evaluation');
    $user = $this->currentUser();

    $form['evaluation'] = [
      '#description_display' => 'before',
    ];

    $is_owner = $this->entity->getOwnerId() == $user->id();
    $feedback = $evaluation->getFeedback();
    if (($is_owner && $user->hasPermission('create own event feedback')) || $user->hasPermission('create any event feedback')) {
      $form['evaluation_user'] = [
        '#description' => $this->t('Choose which user to associate with this feedback.'),
        '#description_display' => 'before',
        '#type' => 'select',
        '#options' => [
          $user->id() => $user->getDisplayName(),
          $this->entity->getOwnerId() => $this->entity->getOwner()->getDisplayName(),
        ],
        '#default_value' => $evaluation->getOwnerId(),
        '#access' => $user->hasPermission('create any event feedback'),
      ];
      $form['evaluation'] += [
        '#title' => $this->t("How'd the Event Go?"),
        '#type' => 'textarea',
        '#attributes' => [
          'placeholder' => $this->t('Add thoughts about your event here to use in the future.'),
        ],
        '#default_value' => $feedback,
        '#description' => $this->t("Ask yourself questions like: Did the event meet your expectations? How does it differ from other events you've held? Did you receive any feedback from attendees?"),
      ];
      $form['actions'] = $this->buildActions();
    }
    else {
      if (empty($feedback)) {
        $form['evaluation']['#type'] = 'item';
        $form['evaluation'] += [
          '#description' => $this->t('There is no feedback yet on the event.'),
        ];
      }
      else {
        $form['evaluation'] = [
          '#theme' => 'event_eval_feedback',
          '#user' => $evaluation->getOwner()->getDisplayName(),
          '#content' => $feedback,
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $evaluation = $form_state->get('evaluation');
    if (!empty($form_state->getValue('evaluation_user'))) {
      $evaluation->setOwnerId($form_state->getValue('evaluation_user'));
    }
    else {
      $evaluation->setOwnerId($this->currentUser()->id());
    }
    $vote = $form_state->getValue('evaluation');
    $evaluation->setFeedback($vote);
  }

}
