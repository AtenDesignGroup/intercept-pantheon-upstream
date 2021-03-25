<?php

namespace Drupal\intercept_event\Plugin\InterceptMessageTemplate;

use Drupal\Core\Form\FormStateInterface;
use Drupal\intercept_messages_sms\InterceptMessageSmsTemplateBase;
use Drupal\intercept_messages\ScheduledMessageTemplateInterface;
use Drupal\intercept_messages\ScheduleSubformTrait;

/**
 * Provides message template for new event attendances.
 *
 * @InterceptMessageTemplate(
 *  id = "attendance_sms_created",
 *  label = @Translation("Attendance Created"),
 *  type = "event_attendance"
 * )
 */
class EventAttendanceSmsCreated extends InterceptMessageSmsTemplateBase implements ScheduledMessageTemplateInterface {

  use ScheduleSubformTrait;

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType = 'event_attendance';

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
  public function getIntervalDescription() {
    return $this->t('The day after the event end that this SMS should be sent.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form = array_merge($form, $this->scheduleSubform());

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
