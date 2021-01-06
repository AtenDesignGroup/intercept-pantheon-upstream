<?php

namespace Drupal\intercept_room_reservation\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an approve form for room reservations.
 */
class RoomReservationApproveForm extends RoomReservationUpdateStatusForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'room_reservation_approve_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to @action this reservation?', [
      '@action' => $this->getStatus()->action,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\intercept_room_reservation\Entity\RoomReservationInterface $entity */
    $entity = $this->entity;
    $warnings = [];
    $violations = $entity->validationWarnings();
    foreach ($violations->getEntityViolations() as $violation) {
      $warnings[] = $violation->getMessage();
    }

    $form['message'] = [
      '#theme' => 'room_reservation_warnings',
      '#warnings' => $warnings,
    ];

    return parent::buildForm($form, $form_state);
  }

}
