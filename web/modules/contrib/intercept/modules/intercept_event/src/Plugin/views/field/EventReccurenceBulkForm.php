<?php

namespace Drupal\intercept_event\Plugin\views\field;

use Drupal\views\Plugin\views\field\BulkForm;

/**
 * Defines an event recurrence operations bulk form element.
 *
 * @ViewsField("event_reccurence_bulk_form")
 */
class EventReccurenceBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No event reccurence selected.');
  }

}
