<?php

namespace Drupal\intercept_event\Plugin\InterceptMessageTemplate;

use Drupal\Core\Form\FormStateInterface;
use Drupal\intercept_messages\EventRegistrationMessageTemplateBase;
use Drupal\intercept_messages\RecipientMessageTemplateInterface;
use Drupal\intercept_messages\ScheduledMessageTemplateInterface;
use Drupal\intercept_messages\ScheduleSubformTrait;
use Drupal\intercept_messages\StatusSubformTrait;

/**
 * Provides message template for event registrations reminder.
 *
 * @InterceptMessageTemplate(
 *  id = "registration_reminder",
 *  label = @Translation("Registration Reminder"),
 *  type = "event_registration"
 * )
 */
class EventRegistrationReminder extends EventRegistrationMessageTemplateBase implements ScheduledMessageTemplateInterface, RecipientMessageTemplateInterface {

  use ScheduleSubformTrait;
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
  public function getIntervalDescription() {
    return $this->t('The day prior to the event start that this email should be sent. Registrations created after this date will not receive a reminder.');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_configuration = parent::defaultConfiguration();
    $default_configuration['user'] = [];
    $default_configuration['user_email_other'] = '';
    $default_configuration['schedule']['interval'] = '';
    return $default_configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form = array_merge($form, $this->recipientSubform());
    $form = array_merge($form, $this->scheduleSubform());
    $form = array_merge($form, $this->recipientSettingsOverrideSubform());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->submitRecipientSubform($form, $form_state);
    $this->submitScheduleSubform($form, $form_state);
    $this->submitRecipientSettingsOverrideSubform($form, $form_state);
  }

}
