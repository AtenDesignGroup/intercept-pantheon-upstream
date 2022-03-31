<?php

namespace Drupal\office_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the formatter.
 *
 * @FieldFormatter(
 *   id = "office_hours",
 *   label = @Translation("Plain text"),
 *   field_types = {
 *     "office_hours",
 *   }
 * )
 */
class OfficeHoursFormatterDefault extends OfficeHoursFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = '(When using multiple slots per day, better use the table formatter.)';
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList $items */
    $elements = [];

    // If no data is filled for this entity, do not show the formatter.
    if ($items->isEmpty()) {
      return $elements;
    }

    $settings = $this->getSettings();
    $third_party_settings = $this->getThirdPartySettings();
    $field_definition = $items->getFieldDefinition();
    // N.B. 'Show current day' may return nothing in getRows(), while other days are filled.
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items */
    $office_hours = $items->getRows($settings, $this->getFieldSettings(), $third_party_settings);

    // If no data is filled for this entity, do not show the formatter.
    if ($items->isEmpty()) {
      return $elements;
    }

    $elements[] = [
      '#theme' => 'office_hours',
      '#parent' => $field_definition,
      // Pass office_hours to twig theming.
      '#office_hours' => $office_hours,
      '#item_separator' => $settings['separator']['days'],
      '#slot_separator' => $settings['separator']['more_hours'],
      '#attributes' => [
        'class' => ['office-hours'],
      ],
      // '#empty' => $this->t('This location has no opening hours.'),
      '#attached' => [
        'library' => [
          'office_hours/office_hours_formatter',
        ],
      ],
      '#cache' => [
        'max-age' => $items->getStatusTimeLeft($settings, $this->getFieldSettings()),
        'tags' => ['office_hours:field.default'],
      ],
    ];

    $elements = $this->addSchemaFormatter($items, $langcode, $elements);
    $elements = $this->addStatusFormatter($items, $langcode, $elements);
    return $elements;
  }

}
