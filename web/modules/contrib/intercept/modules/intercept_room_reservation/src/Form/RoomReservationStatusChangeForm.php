<?php

namespace Drupal\intercept_room_reservation\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\intercept_core\Form\ReservationStatusChangeForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A form to update the status of a reservation.
 */
class RoomReservationStatusChangeForm extends ReservationStatusChangeForm {

  /**
   * Sets the URL options.
   *
   * @param array $options
   *   The array of options. See \Drupal\Core\Url::fromUri() for details on what
   *   it contains.
   *
   * @return $this
   */
  public function setOptions(array $options) {
    $this->options = $options;
    return $this;
  }

  /**
   * Gets the form entity.
   *
   * The form entity which has been used for populating form element defaults.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The current form entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Sets the form entity for populating form element defaults.
   *
   * Usually, the form entity gets updated by EntityFormInterface::submit(),
   * however this may be used to completely exchange the form entity, e.g. when
   * preparing the rebuild of a multi-step form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the current form should operate upon.
   *
   * @return $this
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return 'room_reservation_status_change_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $entity = $this->getEntity();
    if (!$entity) {
      throw new \RuntimeException('No entity provided to ReservationStatusChangeForm.');
    }
    $form_id = $this->getBaseFormId();
    $form_id .= '_' . $entity->getEntityTypeId() . '_' . $this->fieldName;
    $form_id .= '_' . $entity->id();

    return $form_id;
  }


  /**
   * {@inheritdoc}
   *
   * Repeats the ajaxSubmit function code from core, but adds messages wrapper.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->hasAnyErrors()) {
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -1000,
      ];
      $form['#sorted'] = FALSE;
      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('[data-drupal-selector="' . $form['#attributes']['data-drupal-selector'] . '"]', $form));
      // Wrap all messages in a container div in order to help with making
      // them sticky.
      $selector = '.messages';
      $response->addCommand(new InvokeCommand($selector, 'wrapAll', ["<div class='messages--wrapper'></div>"]));
    }
    else {
      $response = $this->successfulAjaxSubmit($form, $form_state);
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -1000,
    ];
    $form['#sorted'] = FALSE;
    $response = new AjaxResponse();
    // Replace the form with an updated version.
    $response->addCommand(new ReplaceCommand('form.' . Html::getId($form_state->getBuildInfo()['form_id']), $form));
    // Wrap all messages in a container div in order to help with making
    // them sticky.
    $selector = '.messages';
    $response->addCommand(new InvokeCommand($selector, 'wrapAll', ["<div class='messages--wrapper'></div>"]));
    return $response;
  }

}
