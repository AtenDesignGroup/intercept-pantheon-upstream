<?php

namespace Drupal\intercept_event\Plugin\views\field;

use Drupal\views\Plugin\views\field\BulkForm;

/**
 * Defines an event attendance operations bulk form element.
 *
 * @ViewsField("event_attendance_bulk_form")
 */
class EventAttendanceBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No event attendance selected.');
  }

}
