<?php

namespace Drupal\office_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
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
    /** @var \Drupal\office_hours\Plugin\Field\FieldWidget\OfficeHoursWeekWidget $widget */

    // First, create a Week widget for the normal weekdays.
    // Use the form we are already in, not by adding a new widget.
    $plugin_id = 'office_hours_default';
    $field_type = 'office_hours';
    $field_definition = $items->getFieldDefinition($field_type);
    $widget = $this->getOfficeHoursPlugin('widget', $plugin_id, $field_definition);
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
      $field_definition = $items->getFieldDefinition($field_type);
      if ($field_definition) {
        // May be empty when installing field.
        $widget = $this->getOfficeHoursPlugin('widget', $plugin_id, $field_definition);
        // Explicitly set an ID/weight between 0 and first season ID.
        $id = 10;
        $widget_form = $widget->form($items, $form, $form_state);
        // @todo #3335549 Decide to use complete form or only ['widget'] part.
        $element[$field_type][$id] = ($widget_form['widget'][0] ?? []) + $widget_form['widget'];
        unset($element[$field_type][$id][0]);
        unset($element[$field_type][$id]['#parents']);
      }
    }

    // Then, add Widgets for the Season days.
    if ($this->getFieldSetting('seasons')) {
      $plugin_id = 'office_hours_season_only';
      $field_type = 'office_hours_season_header';
      $field_definition = $items->getFieldDefinition($field_type);
      if ($field_definition) {
        $widget = $this->getOfficeHoursPlugin('widget', $plugin_id, $field_definition);
        // Create a Widget for each season. @todo Add sorting?
        $seasons = $items->getSeasons(FALSE, TRUE);
        foreach ($seasons as $id => $season) {
          $widget = $this->getOfficeHoursPlugin('widget', $plugin_id, $field_definition);
          $widget->setSeason($season);
          $widget_form = $widget->form($items, $form, $form_state);
          // @todo #3335549 Decide to use complete form or only ['widget'] part.
          $element[$field_type][$id] = $widget_form['widget'];
          unset($element[$field_type][$id]['#parents']);
        }
      }
    }

    // Remove messages from WeekWidget::addMessage();
    // @todo Perhaps first fetch MessengerInterface::all(), then restore.
    \Drupal::messenger()->deleteByType(static::MESSAGE_TYPE);

    // @todo The '#required' is now on main level, not on weekday level.
    return $element;
  }

  /**
   * Instantiate the widget/formatter object from the stored properties.
   *
   * @param string $plugin_type
   *   The plugin type to retrieve: 'widget' or 'formatter'.
   * @param string $plugin_id
   *   The plugin id.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return \Drupal\Core\Field\PluginSettingsInterface|null
   *   A widget or formatter plugin or NULL if the field does not exist.
   */
  protected function getOfficeHoursPlugin($plugin_type, $plugin_id, FieldDefinitionInterface $field_definition) {
    if (!$field_definition) {
      return NULL;
    }

    // @todo Keep aligned between WebformOfficeHours and ~Widget.
    $label = $this->label ?? '';
    $widget_settings = $this->getSettings();
    $pluginManager = \Drupal::service("plugin.manager.field.$plugin_type");
    $plugin = $pluginManager->getInstance([
      'field_definition' => $field_definition,
      'form_mode' => $this->originalMode ?? NULL,
      'view_mode' => $this->viewMode ?? NULL,
      // No need to prepare, defaults have been merged in setComponent().
      'prepare' => FALSE,
      'configuration' => [
        'type' => $plugin_id,
        'field_definition' => $field_definition,
        'view_mode' => $this->originalMode ?? NULL,
        'label' => $label,
        // No need to prepare, defaults have been merged in setComponent().
        'prepare' => FALSE,
        'settings' => $widget_settings,
        'third_party_settings' => [],
      ],
    ]);
    return $plugin;
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
