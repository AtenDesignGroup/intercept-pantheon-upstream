<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'office_hours' field type.
 *
 * @FieldType(
 *   id = "office_hours_season_item",
 *   label = @Translation("Office hours in season"),
 *   list_class = "\Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList",
 *   no_ui = TRUE,
 * )
 */
class OfficeHoursSeasonItem extends OfficeHoursItem {

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // @todo Add random Season ID in past and in near future.
    $value = [];
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function isInRange(int $from, int $to): bool {
    if ($to < $from || $to < 0) {
      // @todo Error. Raise try/catch exception for $to < $from.
      // @todo Undefined result for <0. Raise try/catch exception.
      return FALSE;
    }

    $season = $this->getSeason();
    $result = $season->isInRange($from, $to);
    if ($result == TRUE) {
      $result = parent::isInRange($from, $to);
    }
    return $result;
  }

}
