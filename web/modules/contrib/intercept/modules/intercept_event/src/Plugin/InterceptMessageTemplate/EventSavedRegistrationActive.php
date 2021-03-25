<?php

namespace Drupal\intercept_event\Plugin\InterceptMessageTemplate;

use Drupal\Core\Form\FormStateInterface;
use Drupal\intercept_messages\RecipientSubformTrait;
use Drupal\intercept_messages\InterceptMessageTemplateBase;
use Drupal\intercept_messages\RecipientMessageTemplateInterface;

/**
 * Provides message template for event saved reminder.
 *
 * @InterceptMessageTemplate(
 *  id = "saved_registration_active",
 *  label = @Translation("Saved Event Registration Active"),
 *  type = "flagging"
 * )
 */
class EventSavedRegistrationActive extends InterceptMessageTemplateBase implements RecipientMessageTemplateInterface {

  use RecipientSubformTrait;

  /**
   * Returns the form array for status change.
   */
  public function recipientSubform() {
    return [
      'user' => [
        '#title' => $this->t('Recipient(s) to notify'),
        '#type' => 'checkboxes',
        '#options' => [
          'user' => $this->t('User who saved the event'),
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
  public function defaultConfiguration() {
    $default_configuration = parent::defaultConfiguration();
    $default_configuration['user'] = [];
    $default_configuration['user_email_other'] = '';
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

    $form = array_merge($description, $form, $this->recipientSubform());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->submitRecipientSubform($form, $form_state);
  }

}
