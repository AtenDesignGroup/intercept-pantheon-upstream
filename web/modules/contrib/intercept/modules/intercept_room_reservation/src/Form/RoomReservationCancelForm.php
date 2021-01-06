<?php

namespace Drupal\intercept_room_reservation\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a cancel form for room reservations.
 */
class RoomReservationCancelForm extends RoomReservationUpdateStatusForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'room_reservation_cancel_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\intercept_room_reservation\Entity\RoomReservationInterface $entity */
    $entity = $this->entity;
    $form['notes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Cancellation note'),
      '#rows' => 4,
      '#default_value' => $entity->getNotes(),
      '#description' => $this->t('Briefly describe why this reservation is canceled.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Yes');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Go back');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $status_field = $this->getStatusField();
    /** @var \Drupal\intercept_room_reservation\Entity\RoomReservationInterface $entity */
    $entity = $this->entity;
    $entity->{$status_field}->setValue([$this->getStatus()->value]);
    $entity->setNewRevision();
    if ($notes = $form_state->getValue('notes')) {
      $entity->setNotes($notes);
    }
    $entity->save();
    $this->messenger()->addMessage($this->getMessage(), 'status');
    $entity_type_id = $entity->getEntityTypeId();
    $form_state->setRedirect("entity.$entity_type_id.canonical", [
      $entity_type_id => $entity->id(),
    ]);
  }

}
