<?php

namespace Drupal\intercept_messages;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Intercept message template plugins.
 */
interface RecipientMessageTemplateInterface extends InterceptMessageTemplateInterface {

  /**
   * Returns the form array for message template recipients.
   *
   * @return array
   *   The recipient subform.
   */
  public function recipientSubform();

  /**
   * Sets configuration on submit.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitRecipientSubform(array &$form, FormStateInterface $form_state);

}
