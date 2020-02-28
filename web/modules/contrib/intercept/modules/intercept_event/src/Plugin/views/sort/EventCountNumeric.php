<?php

namespace Drupal\intercept_event\Plugin\views\sort;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Handle sorting of event-related counters.
 *
 * Show future events first sorted in chronological order and
 * then show past events sorted in reverse-chronological order.
 *
 * @ViewsSort("event_count_numeric")
 */
class EventCountNumeric extends SortPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $this->ensureMyTable();
    $debug = true;

    if ($this->field == 'event_registration_count') {
      $alias = 'event_count_value';
      $this->query->addOrderBy(NULL, "(SELECT SUM(related_field_table.field_registrants_count)
      FROM event_registration related_entity_table
      LEFT JOIN event_registration__field_event related_join_table ON related_entity_table.id = related_join_table.entity_id AND related_join_table.deleted = '0'
      LEFT JOIN event_registration__field_registrants related_field_table ON related_entity_table.id = related_field_table.entity_id
      WHERE related_join_table.field_event_target_id = node_field_data.nid)", $this->options['order'], $alias);
    }
    elseif ($this->field == 'event_attendance_count') {
      $alias = 'event_count_value_1';
      $this->query->addOrderBy(NULL, "(SELECT SUM(related_field_table.field_attendees_count)
      FROM event_attendance related_entity_table
      LEFT JOIN event_attendance__field_event related_join_table ON related_entity_table.id = related_join_table.entity_id AND related_join_table.deleted = '0'
      LEFT JOIN event_attendance__field_attendees related_field_table ON related_entity_table.id = related_field_table.entity_id
      WHERE related_join_table.field_event_target_id = node_field_data.nid)", $this->options['order'], $alias);
    }

  }

}
