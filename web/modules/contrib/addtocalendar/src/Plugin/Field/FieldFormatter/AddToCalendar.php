<?php

namespace Drupal\addtocalendar\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\addtocalendar\AddToCalendarApiWidget;
use Drupal\Core\Utility\Token;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'add_to_calendar' formatter.
 *
 * @FieldFormatter(
 *   id = "add_to_calendar",
 *   label = @Translation("Add to calendar"),
 *   field_types = {
 *     "add_to_calendar_field",
 *   }
 * )
 */
class AddToCalendar extends FormatterBase implements ContainerFactoryPluginInterface {


  /**
   * The entity manager service.
   *
   * @var \Drupal\addtocalendar\AddToCalendarApiWidget
   */
  protected $addToCalendarApiWidget;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('addtocalendar.apiwidget'),
      $container->get('token')
    );
  }

  /**
   * Construct an AddToCalendar object.
   *
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Defines an interface for entity field definitions.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\addtocalendar\AddToCalendarApiWidget $add_to_calendar_api_widget
   *   AddToCalendarApi Widget service.
   * @param \Drupal\Core\Utility\Token $token
   *   Token service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AddToCalendarApiWidget $add_to_calendar_api_widget, Token $token) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->addToCalendarApiWidget = $add_to_calendar_api_widget;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary = parent::settingsSummary();
    // Implement settings summary.
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $field_definition = $this->fieldDefinition;
    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $this->viewValue($item)];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    if ($item->value == 1) {
      $entity = $item->getEntity();
      $entity_type = $entity->getEntityTypeId();
      $settings = $this->fieldDefinition->getSettings();

      $service = $this->addToCalendarApiWidget;
      $config_values = [
        'atcStyle' => $settings['addtocalendar_settings']['style'],
        'atcDisplayText' => $this->fieldDefinition->getSetting('on_label'),
        'atcTitle' => $this->getProperValue($settings['addtocalendar_settings']['atc_title'], $entity),
        'atcDescription' => $this->getProperValue($settings['addtocalendar_settings']['atc_description'], $entity),
        'atcLocation' => $this->getProperValue($settings['addtocalendar_settings']['atc_location'], $entity),
        'atcDateStart' => $this->getProperValue($settings['addtocalendar_settings']['atc_date_start'], $entity, ['use_raw_value' => TRUE]),
        'atcDateEnd' => $this->getProperValue($settings['addtocalendar_settings']['atc_date_end'], $entity, ['use_raw_value' => TRUE, 'end_date' => TRUE]),
        'atcOrganizer' => $this->getProperValue($settings['addtocalendar_settings']['atc_organizer'], $entity),
        'atcOrganizerEmail' => $this->getProperValue($settings['addtocalendar_settings']['atc_organizer_email'], $entity),
        'atcPrivacy' => $settings['addtocalendar_settings']['atc_privacy'],
        'atcDataSecure' => $settings['addtocalendar_settings']['data_secure'],
      ];
      if ($settings['addtocalendar_settings']['data_calendars']) {
        $data_calendars = [];
        foreach ($settings['addtocalendar_settings']['data_calendars'] as $key => $set) {
          if ($set) {
            $data_calendars[$key] = $key;
          }
        }
        $config_values['atcDataCalendars'] = $data_calendars;
      }

      $service->setWidgetValues($config_values);
      $build = $service->generateWidget();
      $return = render($build);
    }
    else {
      $return = $this->fieldDefinition->getSetting('off_label');
    }
    return $return;
  }

  /**
   * Generate the output appropriate for one add to calendar setting.
   *
   * @param array $field_setting
   *   The field setting array.
   * @param object $entity
   *   The entity from which the value is to be returned.
   * @param array $options
   *   Provide various options usable to override the data value being return
   *   use 'use_raw_value' to return stored value in database.
   *   use 'end_date' in case of date range fields.
   *
   * @return string
   *   The textual output generated.
   */
  public function getProperValue(array $field_setting, $entity, array $options = []) {
    $entity_type = $entity->getEntityTypeId();
    // Create token service.
    $token_service = $this->token;
    $token_options = [
      'langcode' => $entity->language()->getId(),
      'callback' => '',
      'clear' => TRUE,
    ];
    $value = '';
    switch ($field_setting['field']) {
      case 'token':
        $value = $field_setting['tokenized'];
        $value = $token_service->replace($value, [$entity_type => $entity], $token_options);
        break;

      case 'title':
        $value = $entity->getTitle();
        break;

      default:
        $field = $field_setting['field'];
        if (isset($options['use_raw_value']) && $options['use_raw_value']) {
          $value = strip_tags($entity->{$field}->value);
          if (isset($options['end_date']) && strip_tags($entity->{$field}->getFieldDefinition()->getType()) == 'daterange') {
            $value = strip_tags($entity->{$field}->end_value);
          }
        }
        else {
          $value = $entity->get($field)->view(['label' => 'hidden']);
          $value = strip_tags(render($value));
        }
        break;
    }
    return $value;

  }

}
