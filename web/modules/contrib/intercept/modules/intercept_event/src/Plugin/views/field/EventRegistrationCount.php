<?php

namespace Drupal\intercept_event\Plugin\views\field;

/**
 * Event registration count.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("event_registration_count")
 */
class EventRegistrationCount extends EventRelatedEntityCountBase {

  /**
   * Called to add the field to a query.
   */
  public function query() {
    $data = [
      'entity_table' => 'event_registration',
      'join_table'   => 'event_registration__field_event',
      'field_table'  => 'event_registration__field_registrants',
      'field_column' => 'field_registrants_count',
    ];
    $this->addExpressionField($data);
  }

}
