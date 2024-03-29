<?php

namespace Drupal\clientside_validation_demo\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Implement Class to Validate Clientside DemoForm.
 */
class ClientsideValidationDemoForm extends FormBase {
  use StringTranslationTrait;
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'clientside_validation_demo_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['text_1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text 1'),
      '#description' => $this->t('Simple required text field.'),
      '#required' => TRUE,
    ];

    $form['text_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text 2'),
      '#description' => $this->t('Required text field with custom required_error message.'),
      '#required' => TRUE,
      '#required_error' => $this->t('This message is coming from #required_error.'),
    ];

    $form['text_3'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text 3'),
      '#description' => $this->t('Required text field with max length.'),
      '#required' => FALSE,
      '#maxlength' => 10,
    ];

    $form['text_4'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text 4'),
      '#description' => $this->t('Conditionally required text field.'),
      '#required_error' => $this->t('This message is coming from #required_error with #states.'),
      '#states' => [
        'required' => [':input[name="text_1"]' => ['filled' => FALSE]],
      ],
    ];

    $form['email_1'] = [
      '#type' => 'email',
      '#title' => $this->t('E-Mail 1'),
      '#description' => $this->t('Required E-Mail field.'),
      '#required' => TRUE,
    ];

    $form['email_2'] = [
      '#type' => 'email',
      '#title' => $this->t('E-Mail 2'),
      '#description' => $this->t('E-Mail field.'),
      '#required' => FALSE,
    ];

    $form['number_1'] = [
      '#type' => 'number',
      '#title' => $this->t('Number 1'),
      '#description' => $this->t('Number field.'),
      '#required' => FALSE,
    ];

    $form['number_2'] = [
      '#type' => 'number',
      '#title' => $this->t('Number 2'),
      '#description' => $this->t('Number field with max.'),
      '#max' => 100,
      '#required' => FALSE,
    ];

    $form['number_3'] = [
      '#type' => 'number',
      '#title' => $this->t('Number 3'),
      '#description' => $this->t('Number field with min.'),
      '#min' => 100,
      '#required' => FALSE,
    ];

    $form['number_4'] = [
      '#type' => 'number',
      '#title' => $this->t('Number 4'),
      '#description' => $this->t('Number field with min and max.'),
      '#min' => 100,
      '#max' => 200,
      '#required' => FALSE,
    ];

    $form['number_5'] = [
      '#type' => 'number',
      '#title' => $this->t('Number 5'),
      '#description' => $this->t('Number field with min, max and step.'),
      '#min' => 100,
      '#max' => 200,
      '#step' => 5,
      '#required' => FALSE,
    ];

    $form['url'] = [
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#description' => $this->t('URL field.'),
      '#required' => FALSE,
    ];

    $form['phone_1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone Number'),
      '#size' => 60,
      '#maxlength' => 14,
      '#pattern' => "[789][0-9]{9}",
      '#required' => TRUE,
      '#placeholder' => $this->t('Enter Phone Number - [789][0-9]{9}'),
    ];

    $form['text_equal_1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Equal To'),
      '#description' => $this->t('Field equal to another field.'),
      '#required' => TRUE,
    ];

    $form['text_equal_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Equal to check - default message'),
      '#description' => $this->t('Field equal to another field.'),
      '#equal_to' => 'text_equal_1',
      '#required' => TRUE,
    ];

    $form['text_equal_3'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Equal to check - custom message'),
      '#description' => $this->t('Field equal to another field.'),
      '#equal_to' => 'text_equal_1',
      '#required' => TRUE,
      '#equal_to_error' => $this->t('Text should match value in Equal To.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $form['submit_ajax'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit AJAX'),
      '#ajax' => [
        'callback' => '::submitAjax',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('All form validations passed.'));
  }

  /**
   * Ajax submit callback.
   */
  public function submitAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

}
