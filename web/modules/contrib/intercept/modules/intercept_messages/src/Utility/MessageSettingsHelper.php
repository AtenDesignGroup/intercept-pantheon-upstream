<?php

namespace Drupal\intercept_messages\Utility;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlaggingInterface;
use Drupal\intercept_event\Entity\EventAttendanceInterface;
use Drupal\intercept_event\Entity\EventRegistrationInterface;
use Drupal\user\UserInterface;

/**
 * Helper functions for message settings.
 */
class MessageSettingsHelper {

  /**
   * Whether the user allows email notifications for events.
   *
   * @param \Drupal\user\UserInterface $user
   *   The User entity.
   *
   * @return bool
   *   Whether the user allows email notifications for events, TRUE by default.
   */
  public static function eventEmailEnabled(UserInterface $user) {
    if (NULL == \Drupal::service('user.data')->get('intercept_messages', $user->id(), 'email_event')) {
      return TRUE;
    }
    return (bool) \Drupal::service('user.data')->get('intercept_messages', $user->id(), 'email_event');
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
  public static function eventSmsEnabled(UserInterface $user) {
    return \Drupal::service('user.data')->get('intercept_messages_sms', $user->id(), 'sms_event') ?: FALSE;
  }

  /**
   * Whether the email notification overrides the user settings.
   *
   * @param array $settings
   *   The email notification settings.
   *
   * @return bool
   *   Whether the email settings overrides the user settings, default FALSE.
   */
  public static function eventEmailOverridden(array $settings) {
    if ((bool) $settings['user_settings_override'] === TRUE) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Gets an array of emails from event attendance email settings.
   *
   * @param \Drupal\intercept_event\Entity\EventAttendanceInterface $event_attendance
   *   The event attendance entity.
   * @param array $settings
   *   The email settings array.
   */
  public static function getEventAttendanceEmails(EventAttendanceInterface $event_attendance, array $settings) {
    $addresses = [];
    $email_settings_overridden = self::eventEmailOverridden($settings);
    // Gather the email addresses to send to.
    foreach ($settings['user'] as $user_type) {
      switch ($user_type) {
        case 'user':
        default:
          if ($event_attendance->getAttendee() && (self::eventEmailEnabled($event_attendance->getAttendee()) || $email_settings_overridden)) {
            $addresses[] = $event_attendance->getAttendee()->getEmail();
          }
          break;

        case 'author':
          if ($event_attendance->getOwner() && (self::eventEmailEnabled($event_attendance->getOwner()) || $email_settings_overridden)) {
            $addresses[] = $event_attendance->getOwner()->getEmail();
          }
          break;

        case 'other':
          if (isset($settings['user_email_other'])) {
            $token_replacements = [
              'event_attendance' => $event_attendance,
            ];
            $address_string = \Drupal::service('token')->replace($settings['user_email_other'], $token_replacements, ['clear' => TRUE]);
            $addresses = array_merge(explode(',', $address_string), $addresses);
          }
          break;
      }
    }
    return array_unique($addresses);
  }

  /**
   * Gets the customer telephone from an event attendance.
   *
   * @param \Drupal\intercept_event\Entity\EventAttendanceInterface $event_attendance
   *   The event attendance entity.
   *
   * @return string $telephone
   *   The telephone number of the customer
   */
  public static function getEventAttendancePhone(EventAttendanceInterface $event_attendance) {
    $telephone = '';
    if ($event_attendance->getAttendee() && self::eventSmsEnabled($event_attendance->getAttendee())) {
      $user = $event_attendance->getAttendee();
      $telephone = self::getPhoneNumber($user);
    }
    return $telephone;
  }

  /**
   * Gets an array of emails from event registration email settings.
   *
   * @param \Drupal\intercept_event\Entity\EventRegistrationInterface $event_registration
   *   The event registration entity.
   * @param array $settings
   *   The email settings array.
   */
  public static function getEventRegistrationEmails(EventRegistrationInterface $event_registration, array $settings) {
    $addresses = [];
    $email_settings_overridden = self::eventEmailOverridden($settings);
    // Gather the email addresses to send to.
    foreach ($settings['user'] as $user_type) {
      switch ($user_type) {
        case 'registration_user':
        default:
          if ($event_registration->getRegistrant() && (self::eventEmailEnabled($event_registration->getRegistrant()) || $email_settings_overridden)) {
            $addresses[] = $event_registration->getRegistrant()->getEmail();
          }
          break;

        case 'registration_author':
          if ($event_registration->getOwner() && (self::eventEmailEnabled($event_registration->getOwner()) || $email_settings_overridden)) {
            $addresses[] = $event_registration->getOwner()->getEmail();
          }
          break;

        case 'other':
          if (isset($settings['user_email_other'])) {
            $token_replacements = [
              'event_registration' => $event_registration,
            ];
            $address_string = \Drupal::service('token')->replace($settings['user_email_other'], $token_replacements, ['clear' => TRUE]);
            $addresses = array_merge(explode(',', $address_string), $addresses);
          }
          break;

      }
    }
    return array_unique($addresses);
  }

  /**
   * Gets the customer telephone from an event registration.
   *
   * @param \Drupal\intercept_event\Entity\EventRegistrationInterface $event_registration
   *   The event registration entity.
   *
   * @return string $telephone
   *   The telephone number of the customer
   */
  public static function getEventRegistrationPhone(EventRegistrationInterface $event_registration) {
    $telephone = '';
    if ($event_registration->getRegistrant() && self::eventSmsEnabled($event_registration->getRegistrant())) {
      $user = $event_registration->getRegistrant();
      $telephone = self::getPhoneNumber($user);
    }
    return $telephone;
  }

  /**
   * Gets an array of emails from saved event settings.
   *
   * @param \Drupal\flag\Entity\FlaggingInterface $flagging
   *   The flagging entity.
   * @param array $settings
   *   The email settings array.
   */
  public static function getEventSavedEmails(FlaggingInterface $flagging, array $settings) {
    $addresses = [];
    // Gather the email addresses to send to.
    foreach ($settings['user'] as $user_type) {
      switch ($user_type) {
        case 'user':
        default:
          if ($flagging->getOwner() && self::eventEmailEnabled($flagging->getOwner())) {
            $addresses[] = $flagging->getOwner()->getEmail();
          }
          break;

        case 'other':
          if (isset($settings['user_email_other'])) {
            $token_replacements = [
              'flagging' => $flagging,
            ];
            $address_string = \Drupal::service('token')->replace($settings['user_email_other'], $token_replacements, ['clear' => TRUE]);
            $addresses = array_merge(explode(',', $address_string), $addresses);
          }
          break;
      }
    }
    return array_unique($addresses);
  }

  /**
   * Gets the customer telephone from an event registration.
   *
   * @param \Drupal\flag\Entity\FlaggingInterface $flagging
   *   The flagging entity.
   *
   * @return string $telephone
   *   The telephone number of the customer
   */
  public static function getEventSavedPhone(FlaggingInterface $flagging) {
    $telephone = '';
    if ($flagging->getOwner() && self::eventSmsEnabled($flagging->getOwner())) {
      $user = $flagging->getOwner();
      $telephone = self::getPhoneNumber($user);
    }
    return $telephone;
  }



  /**
   * Gets the recipient phone number.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to check.
   *
   * @return string|null
   *   The recipient phone number, or NULL.
   */
  public static function getPhoneNumber(AccountInterface $user) {
    $profile_storage = \Drupal::service('entity_type.manager')->getStorage('profile');
    $customer = $profile_storage->loadByProperties([
      'type' => 'customer',
      'uid' => $user->id(),
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

  /**
   * Sends an email through the mail manager.
   */
  public static function mail(EntityInterface $entity, $email, array $context) {
    $langcode = \Drupal::service('language_manager')->getDefaultLanguage()->getId();
    $token_replacements = [
      $entity->getEntityTypeId() => $entity,
    ];
    \Drupal::service('plugin.manager.mail')
      ->mail('intercept_messages', 'status_change', $email, $langcode, [
        'context' => [
          'mail_key' => $context['mail_key'],
          'subject' => $context['subject'],
          'body' => $context['body'],
          'tokens' => $token_replacements,
        ],
      ]);
  }

}
