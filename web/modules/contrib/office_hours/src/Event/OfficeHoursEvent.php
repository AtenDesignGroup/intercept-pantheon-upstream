<?php

namespace Drupal\office_hours\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Field\PluginSettingsBase;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface;

/**
 * Defines an Event.
 *
 * @package Drupal\office_hours\Event
 */
class OfficeHoursEvent extends Event {

  /**
   * The Itemlist.
   *
   * @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface
   */
  public $items;

  /**
   * The foramtterd office_hours.
   *
   * @var array
   */
  public $officeHours;

  /**
   * The timestamp to use.
   *
   * @var int
   */
  protected $timestamp;

  /**
   * The widget/formatter, to avoid multiple processing. (@todo)
   *
   * @var \Drupal\Core\Field\PluginSettingsBase
   */
  protected $plugin;

  /**
   * Constructs an event.
   *
   * @param \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items
   *   The office_hours ItemList.
   * @param array $office_hours
   *   The array of already formatted office_hours.
   * @param int $timestamp
   *   The unix timestamp.
   * @param \Drupal\Core\Field\PluginSettingsBase|null $plugin
   *   (@todo) The formatter or widget, in order to avoid multiple processing.
   */
  public function __construct(OfficeHoursItemListInterface $items, array $office_hours, int $timestamp, PluginSettingsBase|NULL $plugin) {
    $this->items = $items;
    $this->officeHours = $office_hours;
    $this->timestamp = $timestamp;
    $this->plugin = $plugin;
  }

  /**
   * Returns the entity at hand.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity at hand.
   */
  public function getEntity() {
    return $this->items->getEntity();
  }

  /**
   * Returns the item list.
   *
   * @return \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface
   *   The ItemList with the office hours values in $items->getValues().
   */
  public function getItems() {
    return $this->items;
  }

  /**
   * Returns the plugin(widget/formatter) at hand.
   *
   * @return \Drupal\Core\Field\PluginSettingsBase|null
   *   The plugin.
   */
  public function getPlugin() {
    return $this->plugin;
  }

  /**
   * Returns the timestamp to be used.
   *
   * @return int
   *   The unix timestamp.
   */
  public function getTimestamp() {
    return $this->timestamp;
  }

}
