<?php

namespace Drupal\office_hours\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface;

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
    $settings = $this->getSettings();

    // Use the 'open_text' and 'current' slot to set the title.
    $current_item = $items->getCurrentSlot();
    if ($current_item) {
      // For this title, print only the weekday, not the exception date.
      // $settings['exceptions']['date_format'] = $settings['day_format']; .
      $settings['exceptions']['date_format'] = 'l';

      // There might be some confusion with yesterday after midnight.
      $title = implode(' ', [
        $this->t(Html::escape($settings['current_status']['open_text'])),
        $current_item->label($settings),
        $current_item->formatTimeSlot($settings),
      ]);
    }
    else {
      $title = $this->t(Html::escape($settings['current_status']['closed_text']));
    }

    return $title;
  }

}
