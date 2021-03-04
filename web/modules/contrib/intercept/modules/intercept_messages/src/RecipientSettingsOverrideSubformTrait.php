<?php

namespace Drupal\intercept_messages;

use Drupal\Core\Form\FormStateInterface;

/**
 * Subform trait to override recipient user settings.
 */
trait RecipientSettingsOverrideSubformTrait {

  /**
   * Returns the form array for message template recipients.
   *
   * @return array
   *   The recipient settings override subform.
   */
  abstract public function recipientSettingsOverrideSubform();

  /**
   * Sets configuration on submit.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitRecipientSettingsOverrideSubform(array &$form, FormStateInterface $form_state) {
    $this->configuration['user_settings_override'] = $form_state->getValue('user_settings_override');
  }

}
