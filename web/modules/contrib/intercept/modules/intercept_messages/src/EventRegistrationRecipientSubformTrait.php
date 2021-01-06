<?php

namespace Drupal\intercept_messages;

use Drupal\Core\Form\FormStateInterface;

/**
 * Subform trait for event registration recipients.
 */
trait EventRegistrationRecipientSubformTrait {

  /**
   * Returns the form array for status change.
   *
   * @return array
   *   The recipient subform.
   */
  public function recipientSubform() {
    return [
      'user' => [
        '#title' => $this->t('Recipient(s) to notify'),
        '#type' => 'checkboxes',
        '#options' => [
          'registration_user' => $this->t('User the registration is for'),
          'registration_author' => $this->t('User that created the registration'),
          'other' => $this->t('Custom email address'),
        ],
        '#description' => $this->t('Send email to specific users. Duplicates will be removed.'),
        '#default_value' => $this->configuration['user'],
      ],
      'user_email_other' => [
        '#title' => $this->t('Custom email address'),
        '#type' => 'textfield',
        '#default_value' => $this->configuration['user_email_other'],
        '#description' => $this->t('Multiple email addresses may be separated by commas. @token', ['@token' => $this->getTokenDescription()]),
        '#states' => [
          'visible' => [
            ':input[name="email[' . $this->pluginDefinition['id'] . '][user][other]"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];
  }

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
