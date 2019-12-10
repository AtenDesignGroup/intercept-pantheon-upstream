<?php

namespace Drupal\intercept_event\Plugin\views\field;

/**
 * Event attendance count.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("event_attendance_count")
 */
class EventAttendanceCount extends EventRelatedEntityCountBase {

  /**
   * Called to add the field to a query.
   */
  public function query() {
    $data = [
      'entity_table' => 'event_attendance',
      'join_table'   => 'event_attendance__field_event',
      'field_table'  => 'event_attendance__field_attendees',
      'field_column' => 'field_attendees_count',
    ];
    $this->addExpressionField($data);
  }

}
