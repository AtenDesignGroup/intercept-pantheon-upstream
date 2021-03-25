<?php

namespace Drupal\intercept_event\Plugin\InterceptMessageTemplate;

use Drupal\Core\Form\FormStateInterface;
use Drupal\intercept_messages_sms\EventRegistrationSmsMessageTemplateBase;
use Drupal\intercept_messages\InterceptMessageTemplateInterface;
use Drupal\intercept_messages\StatusSubformTrait;

/**
 * Provides message template for event registrations now active.
 *
 * @InterceptMessageTemplate(
 *  id = "registration_sms_active",
 *  label = @Translation("Registration Active"),
 *  type = "event_registration"
 * )
 */
class EventRegistrationSmsActive extends EventRegistrationSmsMessageTemplateBase implements InterceptMessageTemplateInterface {

  use StatusSubformTrait;

  /**
   * Returns the status field options.
   *
   * @return array
   *   The status options.
   */
  public function getStatusOptions() {
    $status_options = [];
    $registration_base_fields = $this->entityFieldManager
      ->getBaseFieldDefinitions('event_registration');
    if (isset($registration_base_fields['status'])) {
      $status_options = [
        'any' => $this->t('Any'),
        'empty' => $this->t('Empty (new registration)'),
      ] + $registration_base_fields['status']->getSetting('allowed_values');
    }
    return $status_options;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_configuration = parent::defaultConfiguration();
    $default_configuration['status_original'] = [];
    $default_configuration['status_new'] = [];
    return $default_configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form = array_merge($form, $this->statusSubform());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->submitStatusSubform($form, $form_state);
  }

}
