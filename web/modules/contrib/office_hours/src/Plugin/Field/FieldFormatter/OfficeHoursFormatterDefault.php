<?php

namespace Drupal\office_hours\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the formatter.
 *
 * @FieldFormatter(
 *   id = "office_hours",
 *   label = @Translation("Plain text"),
 *   field_types = {
 *     "office_hours",
 *   },
 * )
 */
class OfficeHoursFormatterDefault extends OfficeHoursFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if (static::class === __CLASS__) {
      // Avoids message when class overridden. Parent repeats it when needed.
      $summary[] = '(When using multiple slots per day, better use the table formatter.)';
    }

    return $summary;
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

    $formatter_settings = $this->getSettings();
    $widget_settings = $this->getFieldSettings();
    $third_party_settings = $this->getThirdPartySettings();

    // N.B. 'Show current day' may return nothing in getRows(),
    // while other days are filled.
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items */
    $office_hours = $items->getRows($formatter_settings, $widget_settings, $third_party_settings, 0, $this);
    // Pass filtered office_hours structures to twig theming.
    $elements[0]['#theme'] = 'office_hours';
    $elements[0]['#office_hours'] = $office_hours;

    $elements = $this->attachSchemaFormatter($items, $langcode, $elements);
    $elements = $this->attachStatusFormatter($items, $langcode, $elements);
    // Sort elements, to have the StatusFormatter on correct position.
    usort($elements, [SortArray::class, 'sortByWeightProperty']);

    if ($this->attachCache) {
      // Since Field cache does not work properly for Anonymous users,
      // .. enable dynamic field update in office_hours_status_update.js.
      // .. add a ['#cache']['max-age'] attribute to $elements.
      $elements += $this->attachCacheData($items, $langcode);
    }

    return $elements;
  }

}
