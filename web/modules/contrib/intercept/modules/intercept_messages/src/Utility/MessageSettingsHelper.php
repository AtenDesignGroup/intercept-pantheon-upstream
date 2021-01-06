<?php

namespace Drupal\intercept_messages\Utility;

use Drupal\Core\Entity\EntityInterface;
use Drupal\flag\FlaggingInterface;
use Drupal\intercept_event\Entity\EventAttendanceInterface;
use Drupal\intercept_event\Entity\EventRegistrationInterface;

/**
 * Helper functions for message settings.
 */
class MessageSettingsHelper {

  /**
   * Gets an array of emails from an event registration email settings.
   *
   * @param \Drupal\intercept_event\Entity\EventAttendanceInterface $event_attendance
   *   The event attendance entity.
   * @param array $settings
   *   The email settings array.
   */
  public static function getEventAttendanceEmails(EventAttendanceInterface $event_attendance, array $settings) {
    $addresses = [];
    // Gather the email addresses to send to.
    foreach ($settings['user'] as $user_type) {
      switch ($user_type) {
        case 'user':
        default:
          if ($event_attendance->getAttendee()) {
            $addresses[] = $event_attendance->getAttendee()->getEmail();
          }
          break;

        case 'author':
          if ($event_attendance->getOwner()) {
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
   * Gets an array of emails from an event registration email settings.
   *
   * @param \Drupal\intercept_event\Entity\EventRegistrationInterface $event_registration
   *   The event registration entity.
   * @param array $settings
   *   The email settings array.
   */
  public static function getEventRegistrationEmails(EventRegistrationInterface $event_registration, array $settings) {
    $addresses = [];
    // Gather the email addresses to send to.
    foreach ($settings['user'] as $user_type) {
      switch ($user_type) {
        case 'registration_user':
        default:
          if ($event_registration->getRegistrant()) {
            $addresses[] = $event_registration->getRegistrant()->getEmail();
          }
          break;

        case 'registration_author':
          if ($event_registration->getOwner()) {
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
          if ($flagging->getOwner()) {
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
