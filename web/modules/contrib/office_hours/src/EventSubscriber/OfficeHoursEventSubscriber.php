<?php

namespace Drupal\office_hours\EventSubscriber;

use Drupal\office_hours\Event\OfficeHoursEvent;
use Drupal\office_hours\Event\OfficeHoursEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts to changes on office_hours field values.
 */
abstract class OfficeHoursEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[OfficeHoursEvents::UPDATE][] = ['updateProcess'];
    $events[OfficeHoursEvents::PRE_FORMAT][] = ['preFormatProcess'];
    $events[OfficeHoursEvents::POST_FORMAT][] = ['postFormatProcess'];
    return $events;
  }

  /**
   * Called whenever the event is dispatched (@todo).
   *
   * @todo Implement EDIT and UPDATE events.
   *
   * @param \Drupal\office_hours\Event\OfficeHoursEvent $event
   *   The Event object.
   */
  public function updateProcess(OfficeHoursEvent $event) {
  }

  /**
   * Called before formatting. $items may be changed.
   *
   * @param \Drupal\office_hours\Event\OfficeHoursEvent $event
   *   The Event object.
   */
  public function preFormatProcess(OfficeHoursEvent $event) {
    // Example, remove a time slot from the day.
    // $event->getItems()->offsetUnset(1);
  }

  /**
   * Called after formatting. $office_hours may be changed.
   *
   * @param \Drupal\office_hours\Event\OfficeHoursEvent $event
   *   The Event object.
   */
  public function postFormatProcess(OfficeHoursEvent $event) {
    // $event->office_hours = ...;
  }

}
