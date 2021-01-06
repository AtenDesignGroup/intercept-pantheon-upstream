<?php

namespace Drupal\intercept_core\Form;

use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Abstract class for entity update status forms.
 */
abstract class EntityUpdateStatusFormBase extends ContentEntityConfirmFormBase {

  use AjaxFormHelperTrait;

  /**
   * Gets the name of the status field.
   */
  abstract protected function getStatusField();

  /**
   * Gets the message to display to the user on submit.
   */
  abstract protected function getMessage();

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // This should be the redirect URL.
    $entity_type_id = $this->entity->getEntityTypeId();
    return Url::fromRoute("entity.$entity_type_id.canonical", [
      $entity_type_id => $this->entity->id(),
    ]);
  }

  /**
   * Gets the status label for the current action.
   */
  protected function getStatus() {
    $prefix = "entity.{$this->entity->getEntityTypeId()}.";
    $map = [
      $prefix . 'cancel_form' => [
        'action' => 'cancel',
        'value' => 'canceled',
      ],
      $prefix . 'approve_form' => [
        'action' => 'approve',
        'value' => 'approved',
      ],
      $prefix . 'deny_form' => [
        'action' => 'deny',
        'value' => 'denied',
      ],
      $prefix . 'archive_form' => [
        'action' => 'archive',
        'value' => 'archived',
      ],
      $prefix . 'request_form' => [
        'action' => 'request',
        'value' => 'requested',
      ],
    ];
    $route_name = $this->getRouteMatch()->getRouteName();
    return !empty($map[$route_name]) ? (object) $map[$route_name] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $this->entity->validate();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $status_field = $this->getStatusField();
    $this->entity->{$status_field}->setValue([$this->getStatus()->value]);
    $this->entity->save();
    if (!$this->isAjax()) {
      $this->messenger()->addMessage($this->getMessage(), 'status');
    }
    $entity_type_id = $this->entity->getEntityTypeId();
    $form_state->setRedirect("entity.$entity_type_id.canonical", [
      $entity_type_id => $this->entity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    if ($this->isAjax()) {
      $actions['cancel'] = [
        '#type' => 'submit',
        '#value' => $this->getCancelText(),
        '#submit' => ['::noSubmit'],
        '#ajax' => [
          'callback' => '::closeDialog',
          'event' => 'click',
        ],
      ];
    }
    return $actions;
  }

  /**
   * Empty submit callback.
   */
  public function noSubmit(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function closeDialog(array &$form, FormStateInterface $form_state) {
    $command = new CloseDialogCommand();
    $response = new AjaxResponse();
    return $response->addCommand($command);
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {
    $command = new RedirectCommand(Url::fromRoute('<current>')->toString());
    $response = new AjaxResponse();
    return $response->addCommand($command);
  }

}
