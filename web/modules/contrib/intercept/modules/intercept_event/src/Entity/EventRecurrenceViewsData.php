<?php

namespace Drupal\intercept_event\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Event Recurrence entities.
 */
class EventRecurrenceViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['event_recurrence']['event_recurrence_bulk_form'] = [
      'title' => $this->t('Event recurrence operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple event recurrences.'),
      'field' => [
        'id' => 'event_recurrence_bulk_form',
      ],
    ];

    return $data;
  }

}
