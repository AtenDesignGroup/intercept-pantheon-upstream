<?php

namespace Drupal\intercept_event\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime_range\Plugin\Field\FieldFormatter\DateRangeCustomFormatter;

/**
 * Plugin implementation of the 'date_range_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "daterange_combined",
 *   label = @Translation("Date range single date"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class DateRangeCombined extends DateRangeCustomFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['date_end_format']['#access'] = FALSE;
    $form['separator']['#access'] = FALSE;
    return $form;
  }

  /**
   * Gets the markup array for the date range.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The date_range start date.
   * @param \Drupal\Core\Datetime\DrupalDateTime $end_date
   *   The date_range end date.
   */
  protected function buildCombinedDate(DrupalDateTime $start_date, DrupalDateTime $end_date = NULL) {
    $this->setTimeZone($start_date);

    $build = [
      '#markup' => $this->formatCombinedDate($start_date, $end_date),
      '#cache' => [
        'contexts' => [
          'timezone',
        ],
      ],
    ];

    return $build;
  }

  /**
   * Formats the combined date_range values.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The date_range start date.
   * @param \Drupal\Core\Datetime\DrupalDateTime $end_date
   *   The date_range end date.
   */
  protected function formatCombinedDate(DrupalDateTime $start_date, DrupalDateTime $end_date) {
    if (!$end_date) {
      return $this->formatDate($start_date);
    }
    $format = $this->getSetting('date_format');
    $timezone = $this->getSetting('timezone_override');
    $sections = preg_split("/(\{.*\})/", $format, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    $date = '';
    foreach ($sections as $format_string) {
      if ($format_string === trim($format_string, '{}')) {
        $date .= $this->dateFormatter->format($start_date->getTimestamp(), 'custom', $format_string, $timezone != '' ? $timezone : NULL);
        continue;
      }
      $format_string = trim($format_string, '{}');
      $date .= $this->dateFormatter->format($end_date->getTimestamp(), 'custom', $format_string, $timezone != '' ? $timezone : NULL);
    }
    return $date;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if (!empty($item->start_date) && !empty($item->end_date)) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
        $start_date = $item->start_date;
        /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
        $end_date = $item->end_date;

        $elements[$delta] = $this->buildCombinedDate($start_date, $end_date);
      }
    }

    return $elements;
  }

}
