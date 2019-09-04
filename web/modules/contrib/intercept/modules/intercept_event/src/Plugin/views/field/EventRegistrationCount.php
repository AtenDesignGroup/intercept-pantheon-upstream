<?php

namespace Drupal\intercept_event\Plugin\views\field;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

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
