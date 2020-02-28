<?php

/**
 * @file
 * Contains intercept_event.views.inc.
 *
 * Views callback for Events.
 */

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Implements hook_views_data_alter().
 */
function intercept_event_views_data_alter(&$data) {
  $data['users_field_data']['uid_current'] = [
    'real field' => 'uid',
    'title' => new TranslatableMarkup('Intercept Current'),
    'help' => new TranslatableMarkup('Filter the view to the currently logged in user but avoid an INNER join.'),
    'filter' => [
      'id' => 'user_current',
      'type' => 'yes-no',
    ],
  ];

  $data['node_field_data']['event_attendance_count'] = [
    'title' => new TranslatableMarkup('Event attendance count'),
    'help' => new TranslatableMarkup('The number of attendees for an event.'),
    'field' => [
      'id' => 'event_attendance_count',
    ],
    'sort' => [
      'id' => 'event_count_numeric', // Points to custom Numeric.php sort handler.
    ],
  ];

  // TODO: Create this definition for all entity types and create a
  // votingapi ticket.
  $entity_type_manager = \Drupal::service('entity_type.manager');
  $entity_type = $entity_type_manager
    ->getDefinition('node');

  if ($entity_type->hasHandlerClass('views_data')) {
    $base_table = $entity_type_manager
      ->getHandler($entity_type->id(), 'views_data')
      ->getViewsTableForEntityType($entity_type);
    $data[$base_table]['vote_relationship'] = [
      'title' => new TranslatableMarkup('Node vote'),
      'help' => new TranslatableMarkup('Limit results to only those entity flagged by a certain flag; Or display information about the flag set on a entity.'),
      'relationship' => [
        'group' => new TranslatableMarkup('Vote'),
        'label' => new TranslatableMarkup('Votes'),
        'base' => 'votingapi_vote',
        'base field' => 'entity_id',
        'relationship field' => 'nid',
        'id' => 'vote_relationship',
        'referenced_entity_type' => $entity_type->id(),
      ],
    ];
  }

  $data['node_field_data']['event_registration_count'] = [
    'title' => new TranslatableMarkup('Event registration count'),
    'help' => new TranslatableMarkup('The number of registrations for an event.'),
    'field' => [
      'id' => 'event_registration_count',
    ],
    'sort' => [
      'id' => 'event_count_numeric', // Points to custom Numeric.php sort handler.
    ],
  ];

  $data['node__field_location']['field_location_target_id']['filter']['id'] = 'numeric_reference';
}
