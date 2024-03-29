<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'office_hours' field type.
 *
 * @FieldType(
 *   id = "office_hours_season_header",
 *   label = @Translation("Office hours season from-to dates"),
 *   list_class = "\Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList",
 *   no_ui = TRUE,
 * )
 */
class OfficeHoursSeasonHeader extends OfficeHoursItem {

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
  public function formatTimeSlot(array $settings) {
    // @todo For now, do not show the season dates in the formatter.
    // The user can set them in the Season name, too.
    // This saves many feature requests :-).
    // $format = 'd-m-Y';
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function label(array $settings) {
    return $this->comment;
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

    // Exclude season headers.
    $result = FALSE;

    return $result;
  }

}
