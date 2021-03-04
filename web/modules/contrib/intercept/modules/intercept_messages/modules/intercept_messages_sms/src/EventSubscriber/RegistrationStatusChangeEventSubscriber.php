<?php

namespace Drupal\intercept_messages_sms\EventSubscriber;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\intercept_core\Event\EntityStatusChangeEvent;
use Drupal\intercept_event\Entity\EventRegistrationInterface;
use Drupal\sms\Direction;
use Drupal\sms\Exception\RecipientRouteException;
use Drupal\sms\Message\SmsMessage;
use Drupal\sms\Provider\SmsProviderInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
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
   * SMS Framework provider.
   *
   * @var \Drupal\sms\Provider\SmsProviderInterface
   */
  protected $smsProvider;

  /**
   * The token utility service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The array of event registration SMS settings.
   *
   * @var array
   */
  protected $smsSettings;

  /**
   * Constructs a RegistrationStatusChangeEventSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A configuration factory instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\sms\Provider\SmsProviderInterface $sms_provider
   *   SMS Framework phone number provider.
   * @param \Drupal\Core\Utility\Token $token
   *   The token utility service.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, SmsProviderInterface $sms_provider, Token $token, UserDataInterface $user_data) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->smsProvider = $sms_provider;
    $this->token = $token;
    $this->userData = $user_data;

    $this->smsSettings = $this->configFactory->get('intercept_event.settings')->get('sms') ?: [];
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
   * Whether the user allows SMS notifications for events.
   *
   * @param \Drupal\user\UserInterface $user
   *   The User entity.
   *
   * @return bool
   *   Whether the user allows SMS notifications for events, FALSE by default.
   */
  protected function eventSmsEnabled(UserInterface $user) {
    return $this->userData->get('intercept_messages_sms', $user->id(), 'sms_event') ?: FALSE;
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

    if ($phone_number = $this->getPhoneNumber($entity)) {
      foreach ($this->getFilteredMessages($event) as $settings) {
        if (!$this->hasRequiredKeys($settings)) {
          continue;
        }

        $sms = new SmsMessage();
        $token_replacements = [
          'event_registration' => $entity,
        ];
        $message = PlainTextOutput::renderFromHtml($this->token->replace($settings['body'], $token_replacements));
        $sms
          ->setMessage($message)
          ->addRecipient($phone_number)
          ->setDirection(Direction::OUTGOING);
        try {
          $this->smsProvider->queue($sms);
        }
        catch (RecipientRouteException $e) {
          // Thrown if no gateway could be determined for the message.
          \Drupal::logger('RecipientRouteException')->warning($e->getMessage());
        }
        catch (\Exception $e) {
          // Other exceptions can be thrown.
          \Drupal::logger('Exception')->warning($e->getMessage());
        }
      }
    }
  }

  /**
   * Gets matching and filtered message settings.
   *
   * @param \Drupal\intercept_core\Event\EntityStatusChangeEvent $event
   *   The entity status change event.
   *
   * @return array
   *   An array of message settings that are filtered.
   */
  protected function getFilteredMessages(EntityStatusChangeEvent $event) {
    $filtered_messages = [];
    if (empty($this->smsSettings)) {
      return $filtered_messages;
    }
    $original_status = $event->getPreviousStatus() ?: 'empty';
    $new_status = $event->getNewStatus();

    foreach ($this->smsSettings as $key => $setting) {
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
      $filtered_messages[$key] = $setting;
    }
    return $filtered_messages;
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
   * Gets the recipient phone number.
   *
   * @param \Drupal\intercept_event\Entity\EventRegistrationInterface $event_registration
   *   The event registration.
   *
   * @return string|null
   *   The recipient phone number, or NULL.
   */
  protected function getPhoneNumber(EventRegistrationInterface $event_registration) {
    $registrant = $event_registration->getRegistrant();
    if (!$registrant) { // Don't send to guests.
      return NULL;
    }
    if (!$this->eventSmsEnabled($registrant)) {
      return NULL;
    }

    $customer = $this->entityTypeManager
      ->getStorage('profile')
      ->loadByProperties([
        'type' => 'customer',
        'uid' => $registrant->id(),
      ]);
    if (!empty($customer)) {
      $customer = array_shift($customer);
      /** @var \Drupal\profile\Entity\ProfileInterface $customer */
      if ($customer->hasField('field_phone')) {
        return $customer->get('field_phone')->value;
      }
    }
    return NULL;
  }

}
