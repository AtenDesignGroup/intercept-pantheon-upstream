<?php

namespace Drupal\addtocalendar\Plugin\Field\FieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem;

/**
 * Plugin implementation of the 'my_test_field' field type.
 *
 * @FieldType(
 *   id = "add_to_calendar_field",
 *   label = @Translation("Add to calendar"),
 *   description = @Translation("This is a field type to provide add to calebdar widget on an entity"),
 *   default_widget = "add_to_calendar_widget_type",
 *   default_formatter = "add_to_calendar",
 * )
 */
class AddToCalendarField extends BooleanItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $addtocalendar_settings = ['style' => 'glow_orange'];
    $settings = [
      'addtocalendar_show' => '1',
      'addtocalendar_settings' => $addtocalendar_settings,
      'on_label' => new TranslatableMarkup('Add to Calendar'),
      'off_label' => new TranslatableMarkup('Add to Calendar Disabled'),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);
    $settings = $this->getSettings();
    $element['off_label']['#required'] = FALSE;
    $field_definition = $this->definition->getFieldDefinition();

    // Build add to calendar widget settings form.
    $element += _addtocalendar_build_form($settings, $field_definition);
    $element['addtocalendar_show']['#title'] = t('Add to Calendar settings');
    $element['on_label']['#title'] = $this->t('Display Text');
    $element['off_label']['#title'] = $this->t('Disabled Text');
    unset($element['addtocalendar_settings']['display_text']);
    return $element;
  }

}
