<?php

namespace Drupal\office_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the formatter, from https://schema.org/openingHours.
 *
 * No field type attached, so not visible on Field's 'Manage display' page.
 *
 * @FieldFormatter(
 *   id = "office_hours_schema_org",
 *   label = @Translation("openingHours support from schema.org"),
 *   field_types = {},
 * )
 */
class OfficeHoursFormatterSchema extends OfficeHoursFormatterBase {

  /**
   * {@inheritdoc}
   *
   * From https://schema.org/openingHours :
   * The general opening hours for a business. Opening hours can be specified
   * as a weekly time range, starting with days, then times per day.
   * Multiple days can be listed with commas ',' separating each day.
   * Day or time ranges are specified using a hyphen '-'.
   * Days are specified using the following two-letter combinations:
   *   Mo, Tu, We, Th, Fr, Sa, Su.
   * Times are specified using 24:00 time. For example,
   *   3pm is specified as 15:00.
   *
   *  Here is a Microdata example:
   *   <time itemprop="openingHours" datetime="Tu,Th 16:00-20:00">
   *     Tuesdays and Thursdays 4-8pm
   *   </time>.
   *  Here is an RDFa example:
   *   <time property="openingHours" datetime="Tu,Th 16:00-20:00">
   *     Tuesdays and Thursdays 4-8pm
   *   </time>.
   *  If a business is open 7 days a week, then it can be specified as
   *   <time itemprop="openingHours" datetime="Mo-Su">
   *     Monday through Sunday, all day
   *   </time>.
   */
  public static function defaultSettings() {
    return [
      // The following settings are fixed in the Microdata settings.
      'day_format' => 'two_letter', // Mo, Tu, We, Th, Fr, Sa, Su.
      'time_format' => 'H', // 24:00 time.
      'separator' => [
        'days' => ', ',
        'grouped_days' => '-',
        'day_hours' => ' ',
        'hours_hours' => '-',
        'more_hours' => ', ',
      ],
      'show_closed' => 'open',
      'current_status' => ['position' => ''], // Hidden.
      'grouped' => 'true',
      'compress' => 'false',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    // Hide the formatter if no data is filled for this entity,
    // or if empty fields must be hidden.
    if ($elements == []) {
      return $elements;
    }

    // Get some settings from field. Do not overwrite defaults.
    $formatter_settings = $this->defaultSettings() + $this->getSettings();
    $widget_settings = $this->getFieldSettings();
    $third_party_settings = $this->getThirdPartySettings();

    // N.B. 'Show current day' may return nothing in getRows(),
    // while other days are filled.
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items */
    $office_hours = $items->getRows($formatter_settings, $widget_settings, $third_party_settings, 0, $this);
    $elements[0]['#theme'] = 'office_hours_schema';
    $elements[0]['#office_hours'] = $office_hours;

    return $elements;
  }

}
