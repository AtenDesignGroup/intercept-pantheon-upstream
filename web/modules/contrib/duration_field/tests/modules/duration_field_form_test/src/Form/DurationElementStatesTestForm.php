<?php

namespace Drupal\duration_field_form_test\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for testing JavaScript #states behavior on 'duration' elements.
 */
class DurationElementStatesTestForm implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'duration_form_element_states_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Trigger element.
    $form['checkbox_trigger'] = [
      '#type' => 'checkbox',
      '#title' => 'Checkbox trigger',
    ];

    // Duration elements that respond to the trigger.
    $form['duration_invisible_when_checkbox_trigger_checked'] = [
      '#type' => 'duration',
      '#title' => 'Duration invisible when checkbox trigger checked',
      '#states' => [
        'invisible' => [
          ':input[name="checkbox_trigger"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

}
