<?php

namespace Drupal\intercept_event\Plugin\InterceptMessageTemplate;

use Drupal\Core\Form\FormStateInterface;
use Drupal\intercept_messages_sms\EventRegistrationSmsMessageTemplateBase;
use Drupal\intercept_messages\InterceptMessageTemplateInterface;

/**
 * Provides message template for event registrations now unwaitlisted.
 *
 * @InterceptMessageTemplate(
 *  id = "registration_sms_unwaitlisted",
 *  label = @Translation("Registration Unwaitlisted"),
 *  type = "event_registration"
 * )
 */
class EventRegistrationSmsUnwaitlisted extends EventRegistrationSmsMessageTemplateBase implements InterceptMessageTemplateInterface {

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
