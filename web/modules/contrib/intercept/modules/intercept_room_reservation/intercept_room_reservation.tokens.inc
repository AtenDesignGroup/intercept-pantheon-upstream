<?php

/**
 * @file
 * Token functions for intercept_room_reservation.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Implements hook_token_info_alter().
 */
function intercept_room_reservation_token_info_alter(&$data) {
  $data['tokens']['room_reservation']['first_name'] = [
    'name' => new TranslatableMarkup("User first name"),
    'description' => new TranslatableMarkup("The first name of this room reservation\'s user."),
  ];
  $data['tokens']['room_reservation']['full_name'] = [
    'name' => new TranslatableMarkup("User full name"),
    'description' => new TranslatableMarkup("The full name of this room reservation\'s user."),
  ];
}

/**
 * Implements hook_tokens().
 */
function intercept_room_reservation_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'room_reservation' && !empty($data['room_reservation'])) {
    /** @var \Drupal\intercept_room_reservation\Entity\RoomReservationInterface $room_reservation */
    $room_reservation = $data['room_reservation'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'first-name':
          $user = $room_reservation->field_user->entity;
          $display_name = $user ? $user->getDisplayName() : '';
          $replacements[$original] = $user && $user->first_name ? $user->first_name : $display_name;
          break;

        case 'full-name':
          $user = $room_reservation->field_user->entity;
          $display_name = $user ? $user->getDisplayName() : '';
          $replacements[$original] = $user && $user->full_name ? $user->full_name : $display_name;
          break;
      }
    }
  }

  return $replacements;
}
