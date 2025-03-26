<?php

namespace Drupal\office_hours\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;

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
class OfficeHoursFormatterTable extends OfficeHoursFormatterDefault {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [$this->t('Display Office hours in a table.')]
      + parent::settingsSummary();
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

    // Fetch the correct element from the already formatted data.
    $hours_formatter = NULL;
    foreach ($elements as $key => $element) {
      switch ($element['#theme'] ?? '') {
        case 'office_hours':
        case 'office_hours_table':
          // Fetch the Office Hours formatter.
          $hours_formatter = &$elements[$key];
          // Leave loop to preserve $key.
          break 2;
      }
    }

    if (!$hours_formatter) {
      return $elements;
    }

    $settings = $this->getSettings();
    $field_definition = $items->getFieldDefinition();
    $field_settings = $this->getFieldSettings();

    // Determine the required columns.
    $row_columns = [];
    // The label header must be hidden if no day label,
    // except when seasons or exceptions are displayed to separate the sections.
    $row_columns['label'] =
      $settings['day_format'] !== 'none'
      || $field_settings['exceptions']
      || $field_settings['seasons'];
    $row_columns['slots'] = TRUE;
    $row_columns['comments'] = (bool) $field_settings['comment'];

    $table_weight = $hours_formatter['#weight'];
    $table_index = $key;
    // Build the table header for each column.
    $header = $this->buildTableHeader($row_columns, FALSE);
    // Build a table element with 0 rows.
    $table_caption = '';
    $table_class = '';
    $table = $this->buildTable($table_caption, $field_definition, $header, $table_rows = [], $table_class);

    // Overwrite parent.
    $hours_formatter['#theme'] = 'office_hours_table';
    $hours_formatter['#table'] = $table;

    // Build/add the table rows.
    $office_hours = $hours_formatter['#office_hours'];
    foreach ($office_hours as $delta => $info) {
      $create_new_table = FALSE;

      $day = $info['day'];
      switch (TRUE) {
        case OfficeHoursDateHelper::isSeasonHeader($day):
          $create_new_table = TRUE;
          $table_class = 'office-hours__table_season';
          $table_caption = $info['caption'];
          // Remove from table, since label etc. is already in caption.
          $info = NULL;
          break;

        case OfficeHoursDateHelper::isExceptionHeader($day):
          $create_new_table = TRUE;
          $table_class = 'office-hours__table_exception';
          $table_caption = $settings['exceptions']['title']
            ? $this->t(Html::escape($settings['exceptions']['title']))
            : '';

          // Remove from table, since label etc. is already in caption.
          $info = NULL;
          break;

        // Case OfficeHoursDateHelper::isExceptionDay($day):
        // Case OfficeHoursDateHelper::isSeasonDay($day):
        // Case OfficeHoursDateHelper::isWeekDay($day):
        default:
          // No new table for weekdays and seasonal weekdays.
          break;
      }

      if ($create_new_table) {
        $create_new_table = FALSE;

        // Retroactively, update the first table.
        // $elements[$key]['#table']['#caption'] = t('Normal hours'); .
        $header = $this->buildTableHeader($row_columns, TRUE);
        $elements[$key]['#table']['#header'] = $header;

        // Prepare the next table.
        $header['label']['data'] = $table_caption;
        $table_caption = '';
        $table = $this->buildTable($table_caption, $field_definition, $header, $table_rows = [], $table_class);

        // Insert new formatter table in correct position.
        array_splice($elements, $table_index, 0, [$hours_formatter]);
        $table_index++;
        // Reset the pointer to the new formatter table.
        $hours_formatter = &$elements[$table_index];
        // Replace with new, clean, empty table.
        $hours_formatter['#table'] = $table;
        $hours_formatter['#weight'] = ++$table_weight;
      }

      // Add row data, unless unset above.
      if ($info) {
        $hours_formatter['#table']['#rows'][$delta] = $this->buildTableRow($info, $row_columns);
      }

    }

    return $elements;
  }

  /**
   * Returns a render array for an empty table. Rows are added later.
   *
   * @param string $table_caption
   *   The table caption, if needed.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $table_header
   *   The render array for the table header.
   * @param array $table_rows
   *   The list of render arrays for the table rows.
   * @param string $table_class
   *   The theme class.
   *
   * @return array
   *   The render array.
   */
  public function buildTable(?string $table_caption, FieldDefinitionInterface $field_definition, array $table_header, array $table_rows, ?string $table_class): array {
    return [
      '#theme' => 'table',
      '#parent' => $field_definition,
      '#caption' => $table_caption,
      '#header' => $table_header,
      // '#empty' => $this->t('This location has no opening hours.'),
      '#rows' => $table_rows,
      '#attributes' => [
        'class' => ['office-hours__table', $table_class],
      ],
      '#attached' => [
        'library' => [
          'office_hours/office_hours_formatter',
        ],
      ],
    ];
  }

  /**
   * Returns a render array for a table header.
   *
   * @param array $row_columns
   *   The details of the row.
   * @param bool $multiple
   *   IF TRUE, multiple tables, so some more column headers are displayed.
   *
   * @return array
   *   The render array.
   */
  protected function buildTableHeader(array $row_columns, bool $multiple): array {
    // Add a label/header/title for accessibility (a11y) screen readers.
    // Superfluous comments are removed. @see #3110755 for examples.
    $element = [];
    $field_settings = $this->getFieldSettings() + ['slots' => TRUE];

    $labels = OfficeHoursItem::getPropertyLabels('data', $field_settings);
    if ($row_columns['label']) {
      $element['label'] = [
        'data' => $labels['day']['data'],
        'colspan' => 3,
        'class' => $multiple ? 'inline' : 'visually-hidden',
      ];
    }
    if ($row_columns['slots']) {
      $element['slots'] = [
        'data' => $labels['slots']['data'],
        'class' => 'visually-hidden',
      ];
    }
    if ($row_columns['comments']) {
      $element['comments'] = [
        'data' => $labels['comment']['data'],
        'class' => 'visually-hidden',
      ];
    }
    return $element;
  }

  /**
   * Returns a render array for a table row.
   *
   * @param array $info
   *   The details of the row.
   * @param array $row_columns
   *   The to be populated columns.
   *
   * @return array
   *   The render array.
   */
  protected function buildTableRow(array $info, array $row_columns): array {
    // Add a label/header/title for accessibility (a11y) screen readers.
    // Superfluous comments are removed. @see #3110755 for examples.
    $element = [
      'no_striping' => TRUE,
      'class' => ['office-hours__item'],
    ];

    // N.B. 'Show current day' may return nothing in getRows(),
    // while other days are filled.
    if ($info['is_current_slot']) {
      $element['class'][] = 'office-hours__item-current';
    }

    $element['data'] = [];
    if ($row_columns['label'] && $this->getSettings()['day_format'] !== 'none') {
      $element['data']['label'] = [
        'data' => ['#markup' => $info['label']],
        // Switch 'Day' between <th> and <tr>.
        'header' => !$row_columns['label'],
        'class' => ['office-hours__item-label'],
      ];
    }

    if ($row_columns['slots']) {
      $element['data']['slots'] = [
        'data' => ['#markup' => $info['formatted_slots']],
        'class' => ['office-hours__item-slots'],
      ];
    }

    if ($row_columns['comments']) {
      $element['data']['comments'] = [
        'data' => ['#markup' => $info['comments']],
        'class' => ['office-hours__item-comments'],
      ];
    }

    return $element;
  }

}
