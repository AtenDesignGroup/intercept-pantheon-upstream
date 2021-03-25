<?php

namespace Drupal\intercept_messages_sms\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides alter functions for the event registration settings form.
 */
class EventAttendanceSmsMessagesSettings extends MessagesSettingsBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'intercept_event.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMessageTemplateTypes() {
    return ['event_attendance'];
  }

  /**
   * Performs the needed alterations to the settings form.
   */
  public function alterSettingsForm(array &$form, FormStateInterface $form_state) {
    $this->setConfig($this->config('intercept_event.settings'));

    parent::alterSettingsForm($form, $form_state);
  }

}
