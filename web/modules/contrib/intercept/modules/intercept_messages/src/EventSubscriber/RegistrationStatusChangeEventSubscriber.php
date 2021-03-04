<?php

namespace Drupal\intercept_messages\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\intercept_core\Event\EntityStatusChangeEvent;
use Drupal\intercept_messages\Utility\MessageSettingsHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for the intercept_entity_status_change event.
 */
class RegistrationStatusChangeEventSubscriber implements EventSubscriberInterface {

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The array of event registration email settings.
   *
   * @var array
   */
  protected $emailSettings;

  /**
   * Constructs a RegistrationStatusChangeEventSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A configuration factory instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->emailSettings = $this->configFactory->get('intercept_event.settings')->get('email') ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      EntityStatusChangeEvent::CHANGE => 'notifyUsers',
    ];
    return $events;
  }

  /**
   * Notifies relevant users of a status change.
   *
   * @param \Drupal\intercept_core\Event\EntityStatusChangeEvent $event
   *   The entity status change event.
   */
  public function notifyUsers(EntityStatusChangeEvent $event) {
    /** @var \Drupal\intercept_event\Entity\EventRegistrationInterface $entity */
    $entity = $event->getEntity();

    if ($entity->getEntityTypeId() != 'event_registration') {
      return;
    }

    foreach ($this->getFilteredEmails($event) as $mail_key => $settings) {
      if (!$this->hasRequiredKeys($settings)) {
        continue;
      }
      foreach (MessageSettingsHelper::getEventRegistrationEmails($entity, $settings) as $email) {
        if ($email) {
          MessageSettingsHelper::mail($entity, $email, [
            'mail_key' => $mail_key,
            'subject' => $settings['subject'],
            'body' => $settings['body'],
          ]);
        }
      }
    }
  }

  /**
   * Checks required keys.
   *
   * @param array $settings
   *   The email settings array.
   *
   * @return bool
   *   Whether the email settings configuration has the required keys.
   */
  protected function hasRequiredKeys(array $settings) {
    $required_keys = [
      'status_original',
      'status_new',
      'subject',
      'body',
    ];
    foreach ($required_keys as $key) {
      if (!array_key_exists($key, $settings) || empty($settings[$key])) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Gets matching and filtered email settings.
   *
   * @param \Drupal\intercept_core\Event\EntityStatusChangeEvent $event
   *   The entity status change event.
   *
   * @return array
   *   An array of email settings that are filtered.
   */
  protected function getFilteredEmails(EntityStatusChangeEvent $event) {
    $filtered_emails = [];
    if (empty($this->emailSettings)) {
      return $filtered_emails;
    }
    $original_status = $event->getPreviousStatus() ?: 'empty';
    $new_status = $event->getNewStatus();

    foreach ($this->emailSettings as $key => $setting) {
      // A setting is disabled if it is not enabled.
      if ((bool) $setting['enabled'] === FALSE) {
        continue;
      }
      // A setting is disabled if there is no original or new status set.
      if (empty($setting['status_original']) || empty($setting['status_new'])) {
        continue;
      }
      $status_original = $setting['status_original'];
      $status_new = $setting['status_new'];
      if (empty($status_original[$original_status]) && empty($status_original['any'])) {
        continue;
      }
      if (empty($status_new[$new_status]) && empty($status_new['any'])) {
        continue;
      }
      $filtered_emails[$key] = $setting;
    }
    return $filtered_emails;
  }

}
