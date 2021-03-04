<?php

namespace Drupal\intercept_messages_sms\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\intercept_messages\Form\UserSettings as MessagesUserSettings;

/**
 * Provides alter functions for the user settings form.
 */
class UserSettings extends MessagesUserSettings {

  /**
   * Performs the needed alterations to the settings form.
   */
  public function alterSettingsForm(array &$form, FormStateInterface $form_state) {
    $user = $form_state->getFormObject()->getEntity();
    if ($user->isAnonymous() || !$user->id()) {
      return;
    }
    if ($this->moduleHandler->moduleExists('intercept_event')) {
      $sms_event_enabled = $this->userData->get('intercept_messages_sms', $user->id(), 'sms_event');
      $form['notifications']['sms_event'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable event notifications by SMS'),
        '#default_value' => isset($sms_event_enabled) ? $sms_event_enabled : FALSE,
      ];
    }
    $form['actions']['submit']['#submit'][] = [$this, 'submitSettingsForm'];
  }

  /**
   * Submit callback for settings form.
   */
  public function submitSettingsForm(array &$form, FormStateInterface $form_state) {
    $user = $form_state->getFormObject()->getEntity();
    if ($this->moduleHandler->moduleExists('intercept_event')) {
      $this->userData->set('intercept_messages_sms', $user->id(), 'sms_event', $form_state->getValue('sms_event'));
    }
  }

}
