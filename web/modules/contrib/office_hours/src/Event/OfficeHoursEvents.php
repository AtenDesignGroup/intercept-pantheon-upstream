<?php

namespace Drupal\office_hours\Event;

/**
 * Office hours Events.
 *
 * @package Drupal\office_hours\Event
 *
 * @see \Drupal\Core\Config\ConfigCrudEvent
 */
final class OfficeHoursEvents {

  /**
   * Event dispatched when Office hours of an entity are changed or displayed.
   *
   * @Event
   *
   * @see \Drupal\office_hours\Event\OfficeHoursEvent
   *
   * @var string
   */

  public const UPDATE = 'office_hours.update';

  /**
   * Event PRE_FORMAT: e.g., adding a holiday for all locations in a region.
   *
   * @Event
   *
   * @see \Drupal\office_hours\Event\OfficeHoursEvent
   *
   * @var string
   */
  public const PRE_FORMAT = 'office_hours.pre_format';

  /**
   * Event POST_FORMAT: e.g., changing time format from 'am' to non-php 'A.M.'.
   *
   * @Event
   *
   * @see \Drupal\office_hours\Event\OfficeHoursEvent
   *
   * @var string
   */
  public const POST_FORMAT = 'office_hours.post_format';

}
