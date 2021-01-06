<?php

namespace Drupal\intercept_event\Plugin\InterceptMessageTemplate;

use Drupal\Core\Form\FormStateInterface;
use Drupal\intercept_messages\RecipientSubformTrait;
use Drupal\intercept_messages\InterceptMessageTemplateBase;
use Drupal\intercept_messages\RecipientMessageTemplateInterface;
use Drupal\intercept_messages\ScheduledMessageTemplateInterface;
use Drupal\intercept_messages\ScheduleSubformTrait;

/**
 * Provides message template for new event attendances.
 *
 * @InterceptMessageTemplate(
 *  id = "attendance_created",
 *  label = @Translation("Attendance Created"),
 *  type = "event_attendance"
 * )
 */
class EventAttendanceCreated extends InterceptMessageTemplateBase implements ScheduledMessageTemplateInterface, RecipientMessageTemplateInterface {

  use RecipientSubformTrait;
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
    $default_configuration['user'] = [];
    $default_configuration['user_email_other'] = '';
    $default_configuration['schedule']['interval'] = '';
    return $default_configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getIntervalDescription() {
    return $this->t('The day after the event end that this email should be sent.');
  }

  /**
   * Returns the form array for status change.
   */
  public function recipientSubform() {
    return [
      'user' => [
        '#title' => $this->t('Recipient(s) to notify'),
        '#type' => 'checkboxes',
        '#options' => [
          'user' => $this->t('User the attendance is for'),
          'author' => $this->t('User that created the attendance'),
          'other' => $this->t('Custom email address'),
        ],
        '#description' => $this->t('Send email to specific users. Duplicates will be removed.'),
        '#default_value' => $this->configuration['user'],
      ],
      'user_email_other' => [
        '#title' => $this->t('Custom email address'),
        '#type' => 'textfield',
        '#default_value' => $this->configuration['user_email_other'],
        '#description' => $this->t('Multiple email addresses may be separated by commas. @token', ['@token' => $this->getTokenDescription()]),
        '#states' => [
          'visible' => [
            ':input[name="email[' . $this->pluginDefinition['id'] . '][user][other]"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form = array_merge($form, $this->recipientSubform());
    $form = array_merge($form, $this->scheduleSubform());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->submitRecipientSubform($form, $form_state);
    $this->submitScheduleSubform($form, $form_state);
  }

}
