<?php

namespace Drupal\office_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of an office_hours widget.
 *
 * @FieldWidget(
 *   id = "office_hours_exceptions",
 *   label = @Translation("Office hours (week) with exceptions and seasons"),
 *   description = @Translation("A widget with sections for weekdays, seasons and exception dates."),
 *   field_types = {
 *     "office_hours",
 *   },
 *   multiple_values = TRUE,
 * )
 */
class OfficeHoursComplexWeekWidget extends OfficeHoursSeasonWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings += [
      'collapsed_exceptions' => TRUE,
    ];
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['collapsed_exceptions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Collapse exceptions'),
      '#default_value' => (bool) $this->getSetting('collapsed_exceptions'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Collapse exceptions: @collapsed', ['@collapsed' => $this->getSetting('collapsed_exceptions') ? $this->t('Yes') : $this->t('No')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // In D8, we have a (deliberate) anomaly in the widget.
    // We prepare 1 widget for the whole week,
    // but the field has unlimited cardinality.
    // So with $delta = 0, we already show ALL values.
    if ($delta > 0) {
      return [];
    }

    // Make form_state not cached since we will update it in ajax callback.
    $form_state->setCached(FALSE);

    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList $items */
    /** @var \Drupal\office_hours\Plugin\Field\FieldWidget\OfficeHoursWidgetBase $widget */

    // First, create a Week widget for the normal weekdays.
    // Use the form we are already in, not by adding a new widget.
    $plugin_id = 'office_hours_default';
    $field_type = 'office_hours';
    $widget = $items->getWidget($plugin_id, $this->getSettings());
    // Explicitly set the season 0, avoiding error upon addMoreSubmit().
    $this->setSeason();
    $id = 0;
    $widget_form = $widget->form($items, $form, $form_state);
    $element[$field_type][$id] = $widget_form['widget'];
    unset($element[$field_type][$id]['#parents']);

    // Then, add a List Widget for the Exception days.
    if ($this->getFieldSetting('exceptions')) {
      $plugin_id = 'office_hours_exceptions_only';
      $field_type = 'office_hours_exceptions';
      $widget = $items->getWidget($plugin_id, $this->getSettings());
      // Explicitly set an ID/weight between weekday (0) and first season ID.
      $id = 10;
      $widget_form = $widget->form($items, $form, $form_state);
      // @todo #3335549 Decide to use complete form or only ['widget'] part.
      $element[$field_type][$id] = ($widget_form['widget'][0] ?? []) + $widget_form['widget'];
      unset($element[$field_type][$id][0]);
      unset($element[$field_type][$id]['#parents']);
    }

    // Then, add Widgets for the Season days.
    if ($this->getFieldSetting('seasons')) {
      $plugin_id = 'office_hours_season_only';
      $field_type = 'office_hours_season_header';
      $widget = $items->getWidget($plugin_id, $this->getSettings());
      // Add a Widget for each season. @todo Add sorting?
      $seasons = $items->getSeasons(FALSE, TRUE);
      foreach ($seasons as $id => $season) {
        $widget->setSeason($season);
        $widget_form = $widget->form($items, $form, $form_state);
        // @todo #3335549 Decide to use complete form or only ['widget'] part.
        $element[$field_type][$id] = $widget_form['widget'];
        unset($element[$field_type][$id]['#parents']);
      }
    }

    // Remove messages from WeekWidget::addMessage();
    // @todo Perhaps first fetch MessengerInterface::all(), then restore.
    \Drupal::messenger()->deleteByType(static::MESSAGE_TYPE);

    // @todo The '#required' is now on main level, not on weekday level.
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $massaged_values = [];

    // @todo Correct $element['#parents'], since FormBuilder's
    // $form_state->setValueForElement() and  $form_state->setUserInput($input)
    // set too much/wrong data, complicating massageFormValues().
    foreach ($values as $widgets) {
      foreach ($widgets as $widget_values) {
        $widget_values = OfficeHoursSeasonWidget::massageFormValues($widget_values, $form, $form_state);
        $massaged_values = array_merge($massaged_values, $widget_values);
      }
    }

    return $massaged_values;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    parent::extractFormValues($items, $form, $form_state);
  }

}
