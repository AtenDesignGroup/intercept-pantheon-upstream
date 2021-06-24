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
    $elements = [];

    // If no data is filled for this entity, do not show the formatter.
    // N.B. 'Show current day' may return nothing in getRows(), while other days are filled.
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items */
    if (!$items->getValue()) {
      return $elements;
    }

    $settings = $this->getSettings();
    // For a11y screen readers, a header is introduced.
    // Superfluous comments are removed. @see #3110755 for examples and explanation.
    $isLabelEnabled = $settings['day_format'] != 'none';
    $isTimeSlotEnabled = TRUE;
    $isCommentEnabled = $this->getFieldSetting('comment');

    // Build the Table part.
    $table_rows = [];
    $office_hours = $this->getRows($items->getValue(), $this->getSettings(), $this->getFieldSettings());
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

    // @todo #2720335 Try to get the meta data into the <tr>.
    /*
    foreach ($table_rows as $delta => &$row) {
      $row['#metadata']['itemprop'] = "openingHours";
      $row['#metadata']['property'] = "openingHours";
      $row['#metadata']['content'] = "todo";
    }
     */

    $table = [
      '#theme' => 'table',
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
      '#office_hours' => $office_hours,
      '#cache' => [
        'max-age' => $this->getStatusTimeLeft($items, $langcode),
        'tags' => ['office_hours:field.table'],
      ],

    ];

    // Build the Schema part from https://schema.org/openingHours.
    if ($settings['schema']['enabled']) {
      $elements[0] = $this->addSchemaFormatter($items, $langcode, $elements[0]);
    }

    // Build the Status part. May reorder elements.
    if ($settings['current_status']['position'] != "") {
      $elements = $this->addStatusFormatter($items, $langcode, $elements);
    }

    return $elements;
  }

}
