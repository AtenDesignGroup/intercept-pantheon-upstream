<?php

namespace Drupal\office_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface;

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
    /* @var $items OfficeHoursItemListInterface */
    if (!$items->getValue()) {
      return $elements;
    }
    $office_hours = $items->getRows($this->getSettings(), $this->getFieldSettings());
    // For a11y screen readers, a header is introduced.
    // Superfluous comments are removed. @see #3110755 for examples and explanation.
    $isCommentEnabled = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('comment');

    // Build the Table part.
    $table_rows = [];
    foreach ($office_hours as $delta => $item) {
      $table_rows[$delta] = [
        'data' => [
          'label' => [
            'data' => ['#markup' => $item['label']],
            'class' => ['office-hours__item-label', ],
            'header' => !$isCommentEnabled,
          ],
          'slots' => [
            'data' => ['#markup' => $item['formatted_slots'] ],
            'class' => ['office-hours__item-slots', ],
          ],
        ],
        'no_striping' => TRUE, // @todo Does not work. Why? Solved in css.
        'class' => ['office-hours__item', ],
      ];

      if ($isCommentEnabled) {
        $table_rows[$delta]['data']['comments'] = [
          'data' => ['#markup' => $item['comments']],
          'class' => ['office-hours__item-comments'],
        ];
      }
 }

// @todo #2720335 Try to get the meta data into the <tr>.
//    foreach ($table_rows as $delta => &$row) {
//      $row['#metadata']['itemprop'] = "openingHours";
//      $row['#metadata']['property'] = "openingHours";
//      $row['#metadata']['content'] = "todo";
//    }

    $table = [
      '#theme' => 'table',
      '#attributes' => [
        'class' => ['office-hours__table', ],
      ],
      //'#empty' => $this->t('This location has no opening hours.'),
      '#rows' => $table_rows,
      '#attached' => [
        'library' => [
          'office_hours/office_hours_formatter',
        ],
      ],
    ];

    if ($isCommentEnabled) {
      $table['#header'] = [
        [
          'data' => $this->t('Day'),
          'class' => 'visually-hidden',
        ],
        [
          'data' => $this->t('Time slot'),
          'class' => 'visually-hidden',
        ],
        [
          'data' => $this->t('Comment'),
          'class' => 'visually-hidden',
        ],
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
    $elements[0] = $this->addSchemaFormatter($items, $langcode, $elements[0]);

    // Build the Status part. May reorder elements.
    $elements = $this->addStatusFormatter($items, $langcode, $elements);

    return $elements;
  }

}
