<?php

namespace Drupal\intercept_event\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
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

  public function getQuestion() {
    return $this->t('Do you really want to @action this registration?', [
      '@action' => $this->getStatus()->action,
    ]);
  }

  protected function getStatusField() {
    return 'status';
  }
  
  protected function getMessage() {
    return $this->t('The registration has been @action', [
      '@action' => $this->getStatus()->value
    ]);
  }
}
