<?php

namespace Drupal\office_hours\Event;

/**
 * Office hours events.
 *
 * @package Drupal\office_hours\Event
 *
 * @see \Drupal\Core\Config\ConfigCrudEvent
 */
final class OfficeHoursEvents {

  /**
   * Event dispatched when Office hours of an entity have changed.
   *
   * @Event
   *
   * @see \Drupal\office_hours\Event\OfficeHoursUpdateEvent
   *
   * @var string
   */
  const OFFICE_HOURS_UPDATE = 'office_hours.hours_update';

}
