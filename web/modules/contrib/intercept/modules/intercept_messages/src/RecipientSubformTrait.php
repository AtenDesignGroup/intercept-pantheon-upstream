<?php

namespace Drupal\intercept_messages;

use Drupal\Core\Form\FormStateInterface;

/**
 * Subform trait for event registration recipients.
 */
trait RecipientSubformTrait {

  /**
   * Returns the form array for message template recipients.
   *
   * @return array
   *   The recipient subform.
   */
  abstract public function recipientSubform();

  /**
   * Sets configuration on submit.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitRecipientSubform(array &$form, FormStateInterface $form_state) {
    $this->configuration['user'] = array_filter($form_state->getValue('user'));
    $this->configuration['user_email_other'] = $form_state->getValue('user_email_other');
  }

}
