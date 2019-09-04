<?php

namespace Drupal\intercept_room_reservation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\intercept_core\Form\EntityUpdateStatusFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class UpdateStatusForm.
 */
class RoomReservationUpdateStatusForm extends EntityUpdateStatusFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'room_reservation_update_status_form';
  }

  public function getQuestion() {
    return $this->t('Do you really want to @action this reservation?', [
      '@action' => $this->getStatus()->action,
    ]);
  }

  protected function getMessage() {
    return $this->t('The reservation has been @action', [
      '@action' => $this->getStatus()->value
    ]);
  }

  protected function getStatusField() {
    return 'field_status';
  }

}
