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
   *   A UNIX time stamp. Defaults to 'REQUEST_TIME'.
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
  public function getRows(array $settings, array $field_settings, array $third_party_settings, int $time = 0, PluginSettingsBase $plugin = NULL);

  /**
   * {@inheritdoc}
   *
   * Create a custom field definition for office_hours_* items.
   *
   * Ideally, we just use the basic 'office_hours' field definition.
   * However, this causes either:
   * 1- to display the 'technical' widgets (exception, season) in Field UI,
   *   (with annotation: field_types = {"office_hours"}), or
   * 2- to have the widget refused by WidgetPluginManager~getInstance().
   *   (with annotation: no_ui = TRUE),
   *   FieldType has annotation 'no_ui', FieldWidget and FieldFormatter haven't.
   * So, the Exceptions and Season widgets are now declared for their
   * specific type.
   *
   * @param string $field_type
   *   The field type, 'office_hours' by default.
   *   If set otherwise a new FieldDefinition is returned.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The field definition. BaseField, not ConfigField,
   *   because easier to construct.
   */
  public function getFieldDefinition($field_type = '');

  /**
   * Get the current slot and the next day from the Office hours.
   *
   * - Attribute 'current' is set on the active slot.
   * - Variable $this->currentSlot is set to slot data.
   * - Variable $this->currentSlot is returned.
   *
   * @param int $time
   *   A UNIX time stamp. Defaults to 'REQUEST_TIME'.
   *
   * @return \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem|null
   *   The current slot data, if any.
   */
  public function getCurrentSlot(int $time = 0);

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
   *   Counter whether the entity has Exception days.
   */
  public function countExceptionDays();

  /**
   * Determines if the Entity is Open or Closed.
   *
   * @param int $time
   *   A UNIX time stamp. Defaults to 'REQUEST_TIME'.
   *
   * @return bool
   *   Indicator whether the entity is Open or Closed at the given time.
   */
  public function isOpen(int $time = 0): bool;

}
