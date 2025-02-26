<?php

namespace Drupal\office_hours\Event;

use Drupal\Core\Field\PluginSettingsBase;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Office hours Event dispatcher.
 *
 * Wrapper methods for the event dispatcher interface.
 *
 * @package Drupal\office_hours\Event
 *
 * @see \Symfony\Component\EventDispatcher\EventDispatcherInterface
 * @see \Drupal\Core\Config\ConfigCrudEvent
 * @todo Extend ContainerAwareEventDispatcher.
 */
final class OfficeHoursEventDispatcher {

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private $eventDispatcher;

  /**
   * Dispatches an Event, allowing registered listeners to update office_hours.
   *
   * The dispatchEvent() function may return an updated $items item list.
   * PRE_FORMAT: e.g., adding a holiday for all locations in a region.
   * POST_FORMAT: e.g., changing the time format from 'am' to non-php 'A.M.'
   *
   * @param string $event_name
   *   The name of the Event to dispatch.
   * @param \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items
   *   The office_hours ItemList.
   * @param array $office_hours
   *   The array of already formatted office_hours.
   * @param int $time
   *   The UNIX timestamp.
   * @param \Drupal\Core\Field\PluginSettingsBase|null $plugin
   *   The formatter or widget, in order to avoid multiple processing.
   *
   * @return \Drupal\office_hours\Event\OfficeHoursEvent
   *   The dispatched Event MUST be returned.
   */
  public function dispatchEvent(string $event_name, OfficeHoursItemListInterface $items, array $office_hours, int $time, PluginSettingsBase|NULL $plugin): OfficeHoursEvent {
    $event = new OfficeHoursEvent($items, $office_hours, $time, $plugin);
    $event = $this->getEventDispatcher()->dispatch($event, $event_name);

    return $event;
  }

  /**
   * Returns the event dispatcher service.
   *
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   *   The event dispatcher service.
   */
  protected function getEventDispatcher() {
    $this->eventDispatcher ??= \Drupal::service('event_dispatcher');
    return $this->eventDispatcher;
  }

  /**
   * Sets the event dispatcher service to use.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The string translation service.
   */
  protected function setEventDispatcher(EventDispatcherInterface $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
  }

}
