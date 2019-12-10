<?php

namespace Drupal\intercept_event\Form;

use Drupal\intercept_core\Form\EntityUpdateStatusFormBase;

/**
 * Class UpdateStatusForm.
 */
class EventRegistrationCancelForm extends EntityUpdateStatusFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_update_status_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you really want to @action this registration?', [
      '@action' => $this->getStatus()->action,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getStatusField() {
    return 'status';
  }

  /**
   * {@inheritdoc}
   */
  protected function getMessage() {
    return $this->t('The registration has been @action', [
      '@action' => $this->getStatus()->value,
    ]);
  }

}
