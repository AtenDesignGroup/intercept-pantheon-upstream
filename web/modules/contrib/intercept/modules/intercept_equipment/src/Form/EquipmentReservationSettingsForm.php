<?php

namespace Drupal\intercept_equipment\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EquipmentReservationSettingsForm.
 *
 * @ingroup intercept_equipment_reservation
 */
class EquipmentReservationSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['intercept_core.equipment_reservations'];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'equipment_reservation_settings';
  }

  /**
   * Defines the settings form for Equipment reservation entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['email'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Emails'),
      '#tree' => TRUE,
    ];

    $emails = [
      'reservation_accepted' => $this->t('Reservation accepted'),
    ];

    foreach ($emails as $key => $title) {
      $form[$key] = [
        '#type' => 'details',
        '#title' => $title,
        '#group' => 'email',
        '#tree' => TRUE,
      ];
      $form[$key]['subject'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#default_value' => $this->getSubject($key),
        '#maxlength' => 180,
      ];
      $form[$key]['body'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Body'),
        '#default_value' => $this->getBody($key),
        '#rows' => 15,
      ];
    }
    return $form;
  }

  /**
   * Gets the equipment reservation email config.
   *
   * @param string $key
   *   The email config key.
   *
   * @return array
   *   The equipment reservation email config array.
   */
  private function getEmailConfig($key) {
    return $this->config('intercept_core.equipment_reservations')->get("email.$key");
  }

  /**
   * Gets the equipment reservation email config subject.
   *
   * @param string $key
   *   The email config key.
   *
   * @return string
   *   The equipment reservation email config subject.
   */
  private function getSubject($key) {
    return !empty($this->getEmailConfig($key)) ? $this->getEmailConfig($key)['subject'] : '';
  }

  /**
   * Gets the equipment reservation email config body.
   *
   * @param string $key
   *   The email config key.
   *
   * @return string
   *   The equipment reservation email config body.
   */
  private function getBody($key) {
    return !empty($this->getEmailConfig($key)) ? $this->getEmailConfig($key)['body'] : '';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('intercept_core.equipment_reservations');
    $values = $form_state->cleanValues()->getValues();
    foreach ($values as $key => $info) {
      if (!empty($info["{$key}__active_tab"])) {
        continue;
      }
      $key = !empty($form[$key]['#group']) ? $form[$key]['#group'] . ".{$key}" : $key;
      $config->set($key, $info);
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
