<?php

/**
 * Add status of "scheduled" to future published events and templates.
 */
function intercept_event_post_update_8001_add_status(&$sandbox) {
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  $nodes_per_batch = 20;

  // Initialize some variables during the first pass through.
  if (!isset($sandbox['total'])) {
    $query = $storage->getQuery()
      ->condition('type', 'event')
      ->condition('status', 1);
    $orGroup = $query->orConditionGroup()
      ->condition('field_date_time.end_value', date(DATETIME_DATETIME_STORAGE_FORMAT, strtotime('now')), '>=')
      ->condition('field_event_is_template', 1);
    $query->condition($orGroup);
    $nids = $query->execute();

    $sandbox['total'] = count($nids);
    $sandbox['ids'] = array_chunk($nids, $nodes_per_batch);
    $sandbox['current'] = 0;
  }

  if ($sandbox['total'] == 0) {
    $sandbox['#finished'] = 1;
    return;
  }

  $nids = array_shift($sandbox['ids']);
  $events = $storage->loadMultiple($nids);

  foreach ($events as $event) {
    if ($event->hasField('field_event_status')) {
      $event->set('field_event_status', 'scheduled');
      $event->save();
    }
    $sandbox['current']++;
  }

  $sandbox['#finished'] = min(($sandbox['current'] / $sandbox['total']), 1);
}
