<?php

namespace Drupal\intercept_messages;

use Drupal\Core\Form\FormStateInterface;

/**
 * Subform trait for status changes.
 */
trait StatusSubformTrait {

  /**
   * Returns the status field options.
   *
   * @return array
   *   The status options.
   */
  abstract public function getStatusOptions();

  /**
   * Returns the form array for status change.
   */
  public function statusSubform() {
    return [
      'status_original' => [
        '#title' => $this->t('Original status'),
        '#type' => 'checkboxes',
        '#multiple' => TRUE,
        '#options' => $this->getStatusOptions(),
        '#description' => $this->t('The previous status. If no value is selected, the message will be inactive.'),
        '#default_value' => $this->configuration['status_original'] ?: '',
      ],
      'status_new' => [
        '#title' => $this->t('New status'),
        '#type' => 'checkboxes',
        '#options' => $this->getStatusOptions(),
        '#description' => $this->t('The new status. If no value is selected, the message will be inactive.'),
        '#default_value' => $this->configuration['status_new'] ?: '',
      ],
    ];
  }

  /**
   * Sets configuration on submit.
   */
  public function submitStatusSubform(&$form, FormStateInterface $form_state) {
    $this->configuration['status_original'] = array_filter($form_state->getValue('status_original'));
    $this->configuration['status_new'] = array_filter($form_state->getValue('status_new'));
  }

}
