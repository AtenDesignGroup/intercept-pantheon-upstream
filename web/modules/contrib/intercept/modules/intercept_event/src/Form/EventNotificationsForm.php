<?php

namespace Drupal\intercept_event\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\intercept_messages\Utility\MessageSettingsHelper;
use Drupal\user\Entity\User;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Event notifications form.
 */
class EventNotificationsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_notifications_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user = User::load(\Drupal::currentUser()->id());
    // Show the form that's used on UserSettings.php in both notification modules.
    // When submitted, update the customer's settings for those modules.
    $event_enabled_email = MessageSettingsHelper::eventEmailEnabled($user);
    $form['notifications']['email_event'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I’d like to receive email updates for my events.'),
      '#default_value' => $event_enabled_email ?? TRUE,
    ];

    if (\Drupal::moduleHandler()->moduleExists('intercept_messages_sms')) {
      $event_enabled_sms = MessageSettingsHelper::eventSmsEnabled($user);
      $form['notifications']['sms_event'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('I’d like to receive text message/SMS updates for my events.'),
        '#default_value' => $event_enabled_sms ?? TRUE,
      ];
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Update the notification settings for the current user.
    $user = User::load(\Drupal::currentUser()->id());
    $event_enabled_email = $form_state->getValue('email_event');
    $event_enabled_sms = $form_state->getValue('sms_event');
    MessageSettingsHelper::eventEmailUpdate($user, $event_enabled_email);
    MessageSettingsHelper::eventSmsUpdate($user, $event_enabled_sms);
    \Drupal::messenger()->addMessage('Your settings have been updated.');
  }

}
