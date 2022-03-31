<?php

namespace Drupal\office_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the formatter.
 *
 * @FieldFormatter(
 *   id = "office_hours_table",
 *   label = @Translation("Table"),
 *   field_types = {
 *     "office_hours",
 *   }
 * )
 */
class OfficeHoursFormatterTable extends OfficeHoursFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Display Office hours in a table.');
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

    // For a11y screen readers, a header is introduced.
    // Superfluous comments are removed. @see #3110755 for examples and explanation.
    $isLabelEnabled = $settings['day_format'] != 'none';
    $isTimeSlotEnabled = TRUE;
    $isCommentEnabled = $this->getFieldSetting('comment');

    // Build the Table part.
    $table_rows = [];
    foreach ($office_hours as $delta => $item) {
      $table_rows[$delta] = [
        'data' => [],
        'no_striping' => TRUE,
        'class' => ['office-hours__item'],
      ];

      if ($isLabelEnabled) {
        $table_rows[$delta]['data']['label'] = [
          'data' => ['#markup' => $item['label']],
          'class' => ['office-hours__item-label'],
          'header' => !$isCommentEnabled, // Switch 'Day' between <th> and <tr>.
        ];
      }
      if ($isTimeSlotEnabled) {
        $table_rows[$delta]['data']['slots'] = [
          'data' => ['#markup' => $item['formatted_slots']],
          'class' => ['office-hours__item-slots'],
        ];
      }
      if ($isCommentEnabled) {
        $table_rows[$delta]['data']['comments'] = [
          'data' => ['#markup' => $item['comments']],
          'class' => ['office-hours__item-comments'],
        ];
      }
    }

    $table = [
      '#theme' => 'table',
      '#parent' => $field_definition,
      '#attributes' => [
        'class' => ['office-hours__table'],
      ],
      // '#empty' => $this->t('This location has no opening hours.'),
      '#rows' => $table_rows,
      '#attached' => [
        'library' => [
          'office_hours/office_hours_formatter',
        ],
      ],
    ];

    if ($isCommentEnabled) {
      if ($isLabelEnabled) {
        $table['#header'][] = [
          'data' => $this->t('Day'),
          'class' => 'visually-hidden',
        ];
      }
      $table['#header'][] = [
        'data' => $this->t('Time slot'),
        'class' => 'visually-hidden',
      ];
      $table['#header'][] = [
        'data' => $this->t('Comment'),
        'class' => 'visually-hidden',
      ];
    }

    $elements[] = [
      '#theme' => 'office_hours_table',
      '#table' => $table,
      // Pass office_hours to twig theming.
      '#office_hours' => $office_hours,
      '#cache' => [
        'max-age' => $items->getStatusTimeLeft($settings, $this->getFieldSettings()),
        'tags' => ['office_hours:field.table'],
      ],

    ];

    $elements = $this->addSchemaFormatter($items, $langcode, $elements);
    $elements = $this->addStatusFormatter($items, $langcode, $elements);
    return $elements;
  }

}
