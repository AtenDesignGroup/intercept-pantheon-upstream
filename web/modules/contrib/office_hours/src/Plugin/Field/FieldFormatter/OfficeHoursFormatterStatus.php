<?php

namespace Drupal\office_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursStatus;

/**
 * Plugin implementation of the formatter.
 *
 * @FieldFormatter(
 *   id = "office_hours_status",
 *   label = @Translation("Status"),
 *   field_types = {
 *     "office_hours_status",
 *   },
 * )
 */
class OfficeHoursFormatterStatus extends OfficeHoursFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element_save = $element['current_status'];

    $element = [];
    $element['current_status'] = $element_save;
    $element['current_status']['#type'] = 'fieldset';
    $element['current_status']['position']['#type'] = 'hidden';
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t("Display only 'Closed'/'Opened' text.");
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    // Alter the default settings, to calculate the cache correctly.
    // The status formatter has no UI for this setting.
    $this->setSetting('show_closed', 'next');
    $formatter_settings = $this->getSettings();
    $options = OfficeHoursStatus::getOptions(NULL, $formatter_settings);

    $elements[0]['#theme'] = 'office_hours_status';
    $elements[0]['#attributes'] = [
      // Empty class is needed for office-hours-status.twig.html file.
      'class' => [],
    ];
    $elements[0] += [
      '#open_text' => (string) $options[OfficeHoursStatus::OPEN],
      '#closed_text' => (string) $options[OfficeHoursStatus::CLOSED],
      '#never_text' => (string) $options[OfficeHoursStatus::NEVER],
      '#position' => $this->settings['current_status']['position'],
    ];

    if ($this->attachCache) {
      // Since Field cache does not work properly for Anonymous users,
      // .. enable dynamic field update in office_hours_status_update.js.
      // .. add a ['#cache']['max-age'] attribute to $elements.
      $elements += $this->attachCacheData($items, $langcode);
    }

    return $elements;
  }

}
