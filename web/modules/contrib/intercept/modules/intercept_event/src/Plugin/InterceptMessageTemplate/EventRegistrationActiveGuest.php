<?php

namespace Drupal\intercept_event\Plugin\InterceptMessageTemplate;

use Drupal\Core\Form\FormStateInterface;
use Drupal\intercept_messages\EventRegistrationMessageTemplateBase;
use Drupal\intercept_messages\RecipientMessageTemplateInterface;

/**
 * Provides message template for guest event registrations now active.
 *
 * @InterceptMessageTemplate(
 *  id = "registration_active_guest",
 *  label = @Translation("Registration Active (Guest)"),
 *  type = "event_registration"
 * )
 */
class EventRegistrationActiveGuest extends EventRegistrationMessageTemplateBase implements RecipientMessageTemplateInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_configuration = parent::defaultConfiguration();
    $default_configuration['status_original'] = [];
    $default_configuration['status_new'] = [];
    $default_configuration['user'] = [];
    $default_configuration['user_email_other'] = '';
    return $default_configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form = array_merge($form, $this->statusSubform());
    $form = array_merge($form, $this->recipientSubform());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->submitStatusSubform($form, $form_state);
    $this->submitRecipientSubform($form, $form_state);
  }

}
