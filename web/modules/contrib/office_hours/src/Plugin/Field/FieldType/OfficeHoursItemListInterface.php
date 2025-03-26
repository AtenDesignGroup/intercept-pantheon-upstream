<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\PluginSettingsBase;

/**
 * Implements ItemListInterface for OfficeHours.
 *
 * @package Drupal\office_hours
 */
interface OfficeHoursItemListInterface extends FieldItemListInterface {

  /**
   * Returns the items of a field.
   *
   * @param array $settings
   *   The formatter settings.
   * @param array $field_settings
   *   The field settings.
   * @param array $third_party_settings
   *   The formatter's third party settings.
   * @param int $time
   *   A UNIX timestamp. If 0, set to 'REQUEST_TIME', alter-hook for Timezone.
   * @param \Drupal\Core\Field\PluginSettingsBase $plugin
   *   The widget/formatter at hand.
   *
   * @return array
   *   The formatted list of slots.
   *
   * @usage The function is not used anymore in module, but is used in local
   * installations theming in twig, skipping the Drupal field UI/formatters.
   * Since twig filters are static methods, a trait is not really an option.
   * Some installations are also subclassing this class.
   */
  public function getRows(array $settings, array $field_settings, array $third_party_settings, int $time = 0, ?PluginSettingsBase $plugin = NULL);

  /**
   * Create an array of seasons. (Do not collect regular or exception days.)
   *
   * @param bool $add_weekdays_as_season
   *   True, if the weekdays must be added as season with ID = 0.
   * @param bool $add_new_season
   *   True, when a default, empty, season must be added.
   * @param string $sort
   *   Empty, 'ascending', 'descending', to sort seasons by start date.
   * @param int $from
   *   Unix timestamp. Only seasons with end date after this date are returned.
   * @param int $to
   *   Unix timestamp. Only seasons with start date before this date are returned.
   *
   * @return \Drupal\office_hours\OfficeHoursSeason[]
   *   A keyed array of seasons. Key = Season ID.
   */
  public function getSeasons($add_weekdays_as_season = FALSE, $add_new_season = FALSE, $sort = '', $from = 0, $to = 0);

  /**
   * Filters out Exception days.
   *
   * @return $this
   *   A filtered clone of the ItemList.
   */
  public function getExceptionItems();

  /**
   * Filters out Season days by Season ID.
   *
   * @param int $season_id
   *   The requested season ID.
   *
   * @return $this
   *   A filtered clone of the ItemList.
   */
  public function getSeasonItems(int $season_id);

  /**
   * Determines if the Entity has Exception days.
   *
   * @return int
   *   Counter for Exception days.
   */
  public function countExceptionDays();

  /**
   * Returns if an entity currently open, currently closed or never open.
   *
   * Decorator function for 'status' subfield.
   *
   * @param int $time
   *   A UNIX timestamp. If 0, set to 'REQUEST_TIME', alter-hook for Timezone.
   *
   * @return int
   *   Indicator: CLOSED = 0, OPEN = 1, NEVER = 2.
   */
  public function getStatus(int $time = 0): int;

  /**
   * Get the current slot and the next day from the Office hours.
   *
   * Decorator function for ItemListFormatter.
   * - Attribute 'current' is set on the active slot.
   * - Variable $this->currentSlot is set to slot data.
   * - Variable $this->currentSlot is returned.
   *
   * @param int $time
   *   A UNIX timestamp. If 0, set to 'REQUEST_TIME', alter-hook for Timezone.
   *
   * @return \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem|null
   *   The current slot data, if any.
   */
  public function getCurrentSlot(int $time = 0);

  /**
   * Returns the slots of the current/next open day.
   *
   * Decorator function for ItemListFormatter.
   *
   * @param int $time
   *   A UNIX timestamp. If 0, set to 'REQUEST_TIME', alter-hook for Timezone.
   *
   * @return \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem[]|null
   *   A list of time slots.
   */
  public function getNextDay(int $time = 0);

  /**
   * Determines if the Entity is Open or Closed at the given time.
   *
   * @param int $time
   *   A UNIX timestamp. If 0, set to 'REQUEST_TIME', alter-hook for Timezone.
   *
   * @return bool
   *   Indicator: CLOSED = FALSE, OPEN = TRUE.
   */
  public function isOpen(int $time = 0): bool;

}
