<?php

namespace Drupal\office_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursSeason;

/**
 * Plugin implementation of the 'office_hours_exceptions' widget.
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
    $field_type = 'office_hours';
    $field_definition = $items->getFieldDefinition($field_type);
    $id = 0;
    // Explicitely set the season 0, avoiding error upon addMoreSubmit().
    $this->setSeason();
    $widget_form = parent::formElement($items, $delta, $element, $form, $form_state);
    $element[$field_type][$id] = $widget_form;
    // $element[$field_type][$id]['#tree'] = TRUE;

    // Then, add a List Widget for the Exception days.
    if ($this->getFieldSetting('exceptions')) {
      $plugin_id = 'office_hours_exceptions_only';
      $field_type = 'office_hours_exceptions';
      $field_definition = $items->getFieldDefinition($field_type);
      $widget = $this->getOfficeHoursPlugin('widget', $plugin_id, $field_definition);
      $id++;
      $widget_form = $widget->form($items, $form, $form_state);
      // @todo #3335549 Decide to use complete form or only ['widget'] part.
      $element[$field_type][$id] = $widget_form['widget'];
      unset($element[$field_type][$id]['#parents']);
      // $element[$field_type][$id]['#tree'] = TRUE;
    }

    // Then, add Widgets for the Season days.
    if ($this->getFieldSetting('seasons')) {
      $plugin_id = 'office_hours_season_only';
      $field_type = 'office_hours_season_header';
      $field_definition = $items->getFieldDefinition($field_type);
      $widget = $this->getOfficeHoursPlugin('widget', $plugin_id, $field_definition);
      // Create a Widget for each season. @todo Add sorting?
      $seasons = $items->getSeasons(FALSE, TRUE);
      foreach ($seasons as $id => $season) {
        $widget->setSeason($season);

        $widget_form = $widget->form($items, $form, $form_state);
        // @todo #3335549 Decide to use complete form or only ['widget'] part.
        $element[$field_type][$id] = $widget_form['widget'];
        unset($element[$field_type][$id]['#parents']);
        // $element[$field_type][$id]['#tree'] = TRUE;
      }
    }

    // Remove messages from WeekWidget::addMessage();
    // @todo Perhaps first fetch MessengerInterface::all(), then restore.
    \Drupal::messenger()->deleteByType(self::MESSAGE_TYPE);

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

    foreach ($values as $widgets) {
      foreach ($widgets as $widget_values) {
        $widget_values = OfficeHoursSeasonWidget::massageFormValues($widget_values, $form, $form_state);
        $massaged_values = array_merge($massaged_values, $widget_values);
      }
    }

    return $massaged_values;
  }

}
