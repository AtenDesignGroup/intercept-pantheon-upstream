<?php

namespace Drupal\intercept_room_reservation\Form;

use Drupal\intercept_core\Form\EntityUpdateStatusFormBase;

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
  protected function getMessage() {
    return $this->t('The reservation has been @action', [
      '@action' => $this->getStatus()->value,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getStatusField() {
    return 'field_status';
  }

}
