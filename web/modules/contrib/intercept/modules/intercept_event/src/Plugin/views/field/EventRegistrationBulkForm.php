<?php

namespace Drupal\intercept_event\Plugin\views\field;

use Drupal\views\Plugin\views\field\BulkForm;

/**
 * Defines an event registration operations bulk form element.
 *
 * @ViewsField("event_registration_bulk_form")
 */
class EventRegistrationBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No event registration selected.');
  }

}
