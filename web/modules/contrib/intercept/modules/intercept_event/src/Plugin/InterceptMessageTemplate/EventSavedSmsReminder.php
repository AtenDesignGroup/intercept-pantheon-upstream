<?php

namespace Drupal\intercept_event\Plugin\InterceptMessageTemplate;

use Drupal\Core\Form\FormStateInterface;
use Drupal\intercept_messages_sms\InterceptMessageSmsTemplateBase;
use Drupal\intercept_messages\ScheduledMessageTemplateInterface;
use Drupal\intercept_messages\ScheduleSubformTrait;

/**
 * Provides message template for event saved reminder.
 *
 * @InterceptMessageTemplate(
 *  id = "saved_sms_reminder",
 *  label = @Translation("Saved Reminder"),
 *  type = "flagging"
 * )
 */
class EventSavedSmsReminder extends InterceptMessageSmsTemplateBase implements ScheduledMessageTemplateInterface {

  use ScheduleSubformTrait;

  /**
   * {@inheritdoc}
   */
  public function getIntervalDescription() {
    return $this->t('The day prior to the event start that this SMS should be sent. Saves created after this date will not receive a reminder.');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_configuration = parent::defaultConfiguration();
    $default_configuration['schedule']['interval'] = '';
    return $default_configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $description = [
      'description' => [
        '#markup' => '<p>This notification is sent as a reminder to customers who save an upcoming non-registration event.</p>',
        '#allowed_tags' => ['p'],
      ]
    ];
    $form = parent::buildConfigurationForm($form, $form_state);

    $form = array_merge($description, $form, $this->scheduleSubform());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->submitScheduleSubform($form, $form_state);
  }

}
