<?php

namespace Drupal\intercept_event\Plugin\InterceptMessageTemplate;

use Drupal\Core\Form\FormStateInterface;
use Drupal\intercept_messages_sms\InterceptMessageSmsTemplateBase;
use Drupal\intercept_messages\InterceptMessageTemplateInterface;

/**
 * Provides message template for event saved reminder.
 *
 * @InterceptMessageTemplate(
 *  id = "saved_sms_registration_active",
 *  label = @Translation("Saved Event Registration Active"),
 *  type = "flagging"
 * )
 */
class EventSavedSmsRegistrationActive extends InterceptMessageSmsTemplateBase implements InterceptMessageTemplateInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_configuration = parent::defaultConfiguration();
    return $default_configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $description = [
      'description' => [
        '#markup' => '<p>This notification is sent to customers who save a registration-required event before registration opens. They will be notified when registration opens so they can sign up.</p>',
        '#allowed_tags' => ['p'],
      ]
    ];

    $form = parent::buildConfigurationForm($form, $form_state);

    $form = array_merge($description, $form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
  }

}
