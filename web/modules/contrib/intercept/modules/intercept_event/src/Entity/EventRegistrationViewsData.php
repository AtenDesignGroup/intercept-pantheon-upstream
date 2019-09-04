<?php

namespace Drupal\intercept_event\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Event Registration entities.
 */
class EventRegistrationViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['event_registration']['event_registration_bulk_form'] = [
      'title' => $this->t('Event registration operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple event registrations.'),
      'field' => [
        'id' => 'event_registration_bulk_form',
      ],
    ];

    return $data;
  }

}
