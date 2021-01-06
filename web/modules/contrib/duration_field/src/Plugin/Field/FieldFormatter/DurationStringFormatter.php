<?php

namespace Drupal\duration_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Provides a ISO 8601 duration string formatter for the Duration field type.
 *
 * @FieldFormatter(
 *   id = "duration_string_display",
 *   label = @Translation("Duration String"),
 *   field_types = {
 *     "duration"
 *   }
 * )
 */
class DurationStringFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = [];

    $summary['summary'] = $this->t('Displays the duration as an <a href=":url">ISO 8601 duration string</a>.', [':url' => 'https://www.iso.org/iso-8601-date-and-time-format.html']);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#markup' => $item->duration,
      ];
    }

    return $element;
  }

}
