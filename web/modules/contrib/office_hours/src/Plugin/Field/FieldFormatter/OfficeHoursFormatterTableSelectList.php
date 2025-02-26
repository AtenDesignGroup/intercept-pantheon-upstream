<?php

namespace Drupal\office_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursStatus;

/**
 * Plugin implementation of the formatter.
 *
 * @FieldFormatter(
 *   id = "office_hours_table_details",
 *   label = @Translation("Table Select list"),
 *   field_types = {
 *     "office_hours",
 *   },
 * )
 */
class OfficeHoursFormatterTableSelectList extends OfficeHoursFormatterTable {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    // @todo Make sure the correct line is overridden.
    $summary = [$this->t('Display Office hours in a openable Select list.')]
    + parent::settingsSummary();
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items */

    // Prevent some user errors in the 'Manage display' Field UI.
    // Activate the 'current_status' position. Needed for attachCacheData().
    $this->settings['current_status']['position'] = 'before';
    // Hide field label, or it would display twice.
    $this->label = 'hidden';
    // Now call the formatter.
    $elements = parent::viewElements($items, $langcode);
    // Remove StatusFormatter. It was used to set 'current_status' '#cache'.
    unset($elements[0]);

    // Hide the formatter if no data is filled for this entity,
    // or if empty fields must be hidden.
    if ($elements == []) {
      return $elements;
    }

    // Convert formatter to Select List ('details' render element).
    $elements = [
      '#type' => 'details',
      '#title' => $this->getStatusTitle($items),
      '#summary_attributes' => [],
    ] + $elements;

    return $elements;
  }

  /**
   * Generates the title for the 'details' formatter.
   *
   * @param \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items
   *   An Office HoursItemList object.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   Title of the element.
   */
  private function getStatusTitle(OfficeHoursItemListInterface $items) {
    $formatter_settings = $this->getSettings();
    // For this title, print only the weekday, not the exception date.
    // $formatter_settings['exceptions']['date_format'] = $formatter_settings['day_format']; .
    $formatter_settings['exceptions']['date_format'] = 'l';

    // Use formatter settings 'open_text' and 'current' to set the title.
    $options = OfficeHoursStatus::getOptions(NULL, $formatter_settings);
    $status = $items->getStatus();
    $title = $options[$status];

    switch ($status) {
      case OfficeHoursStatus::OPEN:
        $current_item = $items->getCurrentSlot();
        $title = implode(' ', [
          $title,
          $current_item->label($formatter_settings),
          $current_item->formatTimeSlot($formatter_settings),
        ]);
        break;

      case OfficeHoursStatus::CLOSED:
      case OfficeHoursStatus::NEVER:
        break;
    }
    return $title;
  }

}
