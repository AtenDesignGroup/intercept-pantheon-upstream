<?php

namespace Drupal\intercept_equipment\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class UpdateStatusForm.
 */
class EquipmentReservationUpdateStatusForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'equipment_reservation_update_status_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you really want to @action this reservation?', [
      '@action' => $this->getStatus()->action,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute("entity.equipment_reservation.canonical", [
      'equipment_reservation' => $this->entity->id(),
    ]);
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
    $this->entity->field_status->setValue([$this->getStatus()->value]);
    $this->entity->save();
    $this->messenger()->addMessage($this->t('The reservation has been @action', ['@action' => $this->getStatus()->status]), 'status');
    $form_state->setRedirect('entity.equipment_reservation.canonical', [
      'equipment_reservation' => $this->entity->id(),
    ]);
  }

}
