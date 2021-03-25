<?php

/**
 * @file
 * Token functions for intercept_event.
 */

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Implements hook_token_info_alter().
 */
function intercept_event_token_info_alter(&$data) {
  $data['tokens']['event_attendance']['first-name'] = [
    'name' => new TranslatableMarkup("User first name"),
    'description' => new TranslatableMarkup("The first name of this event attendance's user."),
  ];
  $data['tokens']['event_attendance']['full-name'] = [
    'name' => new TranslatableMarkup("User full name"),
    'description' => new TranslatableMarkup("The full name of this event attendance's user."),
  ];
  $data['tokens']['event_registration']['first-name'] = [
    'name' => new TranslatableMarkup("User first name"),
    'description' => new TranslatableMarkup("The first name of this event registration's user."),
  ];
  $data['tokens']['event_registration']['full-name'] = [
    'name' => new TranslatableMarkup("User full name"),
    'description' => new TranslatableMarkup("The full name of this event registration's user."),
  ];
}

/**
 * Implements hook_token_info().
 */
function intercept_event_token_info() {
  
  $info['tokens']['flagging']['event-saved-first-name'] = [
    'name' => t('Saved Event - User first name'),
    'description' => t('The first name of this saved event\'s user.'),
  ];
  $info['tokens']['flagging']['event-saved-full-name'] = [
    'name' => t('Saved event - User full name'),
    'description' => t('The full name of this saved event\'s user.'),
  ];
  $info['tokens']['flagging']['event-saved-title'] = [
    'name' => t('Saved event - Title'),
    'description' => t('The title of the saved event node.'),
  ];
  $info['tokens']['flagging']['event-saved-field-date-time'] = [
    'name' => t('Saved event - Date and time'),
    'description' => t('The date and time of the saved event node.'),
  ];
  $info['tokens']['flagging']['event-saved-url'] = [
    'name' => t('Saved event - URL'),
    'description' => t('A plaintext version of the event\'s URL.'),
  ];
  return $info;
}

/**
 * Implements hook_tokens().
 */
function intercept_event_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'event_registration' && !empty($data['event_registration'])) {
    /** @var \Drupal\intercept_event\Entity\EventRegistrationInterface $event_registration */
    $event_registration = $data['event_registration'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'first-name':
          $user = $event_registration->getRegistrant();
          $display_name = $user ? $user->getDisplayName() : '';
          $replacements[$original] = $user && $user->first_name ? $user->first_name : $display_name;
          break;

        case 'full-name':
          $user = $event_registration->getRegistrant();
          $display_name = $user ? $user->getDisplayName() : '';
          $replacements[$original] = $user && $user->full_name ? $user->full_name : $display_name;
          break;
      }
    }
  }

  if ($type == 'event_attendance' && !empty($data['event_attendance'])) {
    /** @var \Drupal\intercept_event\Entity\EventAttendanceInterface $event_attendance */
    $event_attendance = $data['event_attendance'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'first-name':
          $user = $event_attendance->getAttendee();
          $replacements[$original] = $user && $user->first_name ? $user->first_name : 'User';
          break;

        case 'full-name':
          $user = $event_attendance->getAttendee();
          $replacements[$original] = $user && $user->full_name ? $user->full_name : 'User';
          break;
      }
    }
  }

  // Saved Events
  if ($type == 'flagging' && !empty($data['flagging'])) {
    /** @var \Drupal\flag\Entity\Flagging $flag */
    $flagging = $entity = $data['flagging'];
    $saved_event_id = $entity->get('entity_id')->getString();
    $event = Node::load($saved_event_id);
    $user = $flagging->getOwner();


    // Fill in first-name, full-name, title, and field_date_time with
    // info from the flagging (saved event).
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'event-saved-title':
          $replacements[$original] = $event && $event->getTitle() ? $event->getTitle() : 'Library Event';
          break;
        case 'event-saved-field-date-time':
          $dateTime = new DrupalDateTime($event->get('field_date_time')->value, 'UTC');
          $saved_event_date = date('m/d/y \a\t g:i a', $dateTime->getTimestamp());
          $replacements[$original] = $saved_event_date;
          break;
        case 'event-saved-first-name':
          $replacements[$original] = $user && $user->first_name ? $user->first_name : 'User';
          break;
        case 'event-saved-full-name':
          $replacements[$original] = $user && $user->full_name ? $user->full_name : 'User';
          break;
        case 'event-saved-url':
          $url = Url::fromRoute('entity.node.canonical', ['node' => $saved_event_id], ['absolute' => true])->toString();
          $replacements[$original] = $url;
          break;
      }
    }
  }

  return $replacements;
}
