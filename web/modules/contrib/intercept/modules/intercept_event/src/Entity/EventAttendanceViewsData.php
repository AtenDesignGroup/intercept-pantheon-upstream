<?php

namespace Drupal\intercept_event\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Event Attendance entities.
 */
class EventAttendanceViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['event_attendance']['event_attendance_bulk_form'] = [
      'title' => $this->t('Event attendance operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple event attendances.'),
      'field' => [
        'id' => 'event_attendance_bulk_form',
      ],
    ];

    return $data;
  }

}
