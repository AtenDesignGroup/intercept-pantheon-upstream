<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemListInterface;

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
   * @param array $field_settings
   * @param $time
   *
   * @return array
   *   The formatted list of slots.
   *
   * @usage The function is not used anymore in module, but is used in local
   * installations theming in twig, skipping the Drupal field UI/formatters.
   * Since twig filters are static methods, using a trait isnt really an option.
   * Some installations are also subclassing this class.
   */
  public function getRows(array $settings, array $field_settings, $time = NULL);

  /**
   * Determines if the Entity is Open or Closed.
   *
   * @param int $time
   *
   * @return bool
   *   Indicator whether the entity is Open or Closed at the given time.
   */
  public function isOpen($time = NULL);

}
