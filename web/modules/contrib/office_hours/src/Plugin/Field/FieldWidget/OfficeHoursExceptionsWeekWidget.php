<?php

namespace Drupal\office_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'office_hours_exceptions' widget.
 *
 * @FieldWidget(
 *   id = "office_hours_exceptions",
 *   label = @Translation("Office hours (week) with exceptions and seasons"),
 *   field_types = {
 *     "office_hours",
 *   },
 *   multiple_values = "FALSE",
 * )
 */
class OfficeHoursExceptionsWeekWidget extends OfficeHoursSeasonWidget {

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

    // First, create a Week widget for the normal weekdays.
    // Use the form we are already in, not by adding a new widget.
    $field_type = 'office_hours';
    $element[$field_type] = parent::formElement($items, $delta, $element, $form, $form_state);
    $items->filterEmptyItems();

    // Then, add a List Widget for the Exception days.
    /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList $items */
    if ($this->getFieldSetting('exceptions')) {
      $field_type = 'office_hours_exceptions';
      $plugin_id = 'office_hours_exceptions_only';
      $field_definition = $items->getFieldDefinition($field_type);
      /** @var \Drupal\Core\Field\WidgetInterface $widget */
      $widget = $this->getOfficeHoursPlugin('widget', $plugin_id, $field_definition);
      $widget_form = $widget->form($items, $form, $form_state);
      // @todo #3335549 Decide to use complete form or only ['widget'] part.
      $element[$field_type] = $widget_form['widget'];
      unset($element[$field_type]['#after_build']);
      unset($element[$field_type]['#parents']);
      unset($element[$field_type]['#tree']);
    }

    // Then, add Widgets for the Season days.
    if ($this->getFieldSetting('seasons')) {

      // Create an array of seasons. (Do not collect regular or exception days.)
      $seasons = $items->getSeasons(FALSE, TRUE);

      // Create a Widget for each season.
      $field_type = 'office_hours_season';
      $plugin_id = 'office_hours_season_only';
      $field_definition = $items->getFieldDefinition($field_type);
      foreach ($seasons as $id => $season) {
        /** @var \Drupal\office_hours\Plugin\Field\FieldWidget\OfficeHoursSeasonWidget $widget */
        $widget = $this->getOfficeHoursPlugin('widget', $plugin_id, $field_definition);
        $widget->setSeason($season);
        $widget_form = $widget->form($items, $form, $form_state);
        // @todo #3335549 Decide to use complete form or only ['widget'] part.
        $element[$field_type][$id] = $widget_form['widget'];
        unset($element[$field_type][$id]['#after_build']);
        unset($element[$field_type][$id]['#parents']);
        unset($element[$field_type][$id]['#tree']);
      }
    }
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
    $settings = $this->getSettings();
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
        'settings' => $settings,
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
    foreach ($values as $field_type => $widget_values) {

      switch ($field_type) {
        case 'office_hours':
          // Normal weekdays.
          $widget_values = parent::massageFormValues($widget_values, $form, $form_state);
          $massaged_values = array_merge($massaged_values, $widget_values);
          break;

        case 'office_hours_exceptions':
          if (is_array($widget_values['value'])) {
            $widget_values = parent::massageFormValues($widget_values, $form, $form_state);
            $massaged_values = array_merge($massaged_values, $widget_values);
          }
          break;

        case 'office_hours_season':
          // Seasonal Weekdays, grouped per season.
          foreach ($widget_values as $season_id => $season_values) {
            $season_values = parent::massageFormValues($season_values, $form, $form_state);
            $massaged_values = array_merge($massaged_values, $season_values);
          }
          break;

      }

    }
    return $massaged_values;
  }

}
