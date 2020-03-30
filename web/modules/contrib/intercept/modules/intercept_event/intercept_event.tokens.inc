<?php

/**
 * @file
 * Token functions for intercept_event.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Implements hook_token_info_alter().
 */
function intercept_event_token_info_alter(&$data) {
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
          $user = $event_registration->field_user->entity;
          $replacements[$original] = $user && $user->first_name ? $user->first_name : 'User';
          break;

        case 'full-name':
          $user = $event_registration->field_user->entity;
          $replacements[$original] = $user && $user->full_name ? $user->full_name : 'User';
          break;
      }
    }
  }

  return $replacements;
}
