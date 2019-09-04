<?php


/**
 * Implements hook_token_info_alter().
 */
function intercept_room_reservation_token_info_alter(&$data) {
  $data['tokens']['room_reservation']['first_name'] = [
    'name' => t("User first name"),
    'description' => t("The first name of this room reservation\'s user."),
  ];
  $data['tokens']['room_reservation']['full_name'] = [
    'name' => t("User full name"),
    'description' => t("The full name of this room reservation\'s user."),
  ];
}

/**
 * Implements hook_tokens().
 */
function intercept_room_reservation_tokens($type, $tokens, array $data, array $options, \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }
  $replacements = [];

  if ($type == 'room_reservation' && !empty($data['room_reservation'])) {
    /** @var \Drupal\intercept_room_reservation\Entity\RoomReservationInterface $room_reservation */
    $room_reservation = $data['room_reservation'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'first-name':
          $user = $room_reservation->field_user->entity;
          $replacements[$original] = $user && $user->first_name ? $user->first_name : 'User';
          break;
        case 'full-name':
          $user = $room_reservation->field_user->entity;
          $replacements[$original] = $user && $user->full_name ? $user->full_name : 'User';
          break;
        }
      }
    }

  return $replacements;
}
