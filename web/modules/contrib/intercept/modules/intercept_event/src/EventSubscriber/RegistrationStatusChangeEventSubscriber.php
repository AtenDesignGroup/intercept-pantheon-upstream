<?php

namespace Drupal\intercept_event\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\intercept_core\Event\EntityStatusChangeEvent;
use Drupal\intercept_event\Entity\EventRegistrationInterface;
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
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The token utility service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

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
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Utility\Token $token
   *   The token utility service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, MailManagerInterface $mail_manager, Token $token) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->mailManager = $mail_manager;
    $this->token = $token;
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
      $langcode = $this->languageManager->getDefaultLanguage()->getId();
      $token_replacements = [
        'event_registration' => $entity,
      ];
      foreach ($this->getEventRegistrationEmails($entity, $settings) as $email) {
        $this->mailManager->mail('intercept_event', 'status_change', $email, $langcode, [
          'context' => [
            'mail_key' => $mail_key,
            'subject' => $settings['subject'],
            'body' => $settings['body'],
            'tokens' => $token_replacements,
          ],
        ]);
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
    $original_status = $event->getPreviousStatus();
    $new_status = $event->getNewStatus();

    foreach ($this->emailSettings as $key => $setting) {
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

  /**
   * Gets an array of emails from an event registration email settings.
   *
   * @param \Drupal\intercept_event\Entity\EventRegistrationInterface $event_registration
   *   The event registration entity.
   * @param array $settings
   *   The email settings array.
   */
  protected function getEventRegistrationEmails(EventRegistrationInterface $event_registration, array $settings) {
    $addresses = [];
    // Gather the email addresses to send to.
    foreach ($settings['user'] as $user_type) {
      switch ($user_type) {
        case 'registration_user':
        default:
          $addresses[] = $event_registration->getRegistrant()->getEmail();
          break;

        case 'registration_author':
          $addresses[] = $event_registration->getOwner()->getEmail();
          break;

        case 'other':
          if (isset($settings['user_email_other'])) {
            $token_replacements = [
              'event_registration' => $event_registration,
            ];
            $address_string = $this->token->replace($settings['user_email_other'], $token_replacements);
            $addresses = array_merge(explode(',', $address_string), $addresses);
          }
          break;
      }
    }
    return array_unique($addresses);
  }

}
