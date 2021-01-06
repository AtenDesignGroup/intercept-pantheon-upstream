<?php

namespace Drupal\duration_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\duration_field\Service\DurationServiceInterface;
use Drupal\duration_field\Service\GranularityServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a human friendly formatter for the Duration field type.
 *
 * @FieldFormatter(
 *   id = "duration_human_display",
 *   label = @Translation("Human Friendly"),
 *   field_types = {
 *     "duration"
 *   }
 * )
 */
class DurationHumanDisplayFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The granularity service.
   *
   * @var \Drupal\duration_field\Service\GranularityServiceInterface
   */
  protected $granularityService;

  /**
   * The Duration service.
   *
   * @var \Drupal\duration_field\Service\DurationServiceInterface
   */
  protected $durationService;

  /**
   * Constructs a DurationHumanDisplayFormatter object.
   *
   * @param string $plugin_id
   *   The ID of the plugin.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param mixed $field_config
   *   The field definition.
   * @param array $settings
   *   The field settings.
   * @param mixed $label
   *   The label of the field.
   * @param string $view_mode
   *   The current view mode.
   * @param array $third_party_settings
   *   The third party settings.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\duration_field\Service\GranularityServiceInterface $granularityService
   *   The granularity service.
   * @param \Drupal\duration_field\Service\DurationServiceInterface $durationService
   *   The duration service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    $field_config,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    ModuleHandlerInterface $moduleHandler,
    GranularityServiceInterface $granularityService,
    DurationServiceInterface $durationService
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_config, $settings, $label, $view_mode, $third_party_settings);

    $this->moduleHandler = $moduleHandler;
    $this->granularityService = $granularityService;
    $this->durationService = $durationService;
  }

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
      $container->get('module_handler'),
      $container->get('duration_field.granularity.service'),
      $container->get('duration_field.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $settings = $this->getSettings();

    $summary['summary'] = $this->t('Displays the duration in a human-friendly format');
    $summary['text_length'] = $this->t('Format: @text_length', ['@text_length' => $this->getHumanFriendlyLabel($settings['text_length'], FALSE)]);
    $summary['separator'] = $this->t('Separator: @separator', ['@separator' => $this->getHumanFriendlyLabel($settings['separator'], FALSE)]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {

    return [
      'text_length' => 'full',
      'separator' => 'space',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element['text_length'] = [
      '#title' => $this->t('Text length'),
      '#type' => 'select',
      '#options' => [
        'full' => $this->getHumanFriendlyLabel('full'),
        'short' => $this->getHumanFriendlyLabel('short'),
      ],
      '#default_value' => $this->getSetting('text_length'),
    ];

    $custom_separators = $this->moduleHandler->invokeAll('duration_field_separators');
    $custom_separator_mappings = [];
    foreach (array_keys($custom_separators) as $custom_separator) {
      $custom_separator_mappings[$custom_separator] = $this->getHumanFriendlyLabel($custom_separator);
    }

    $element['separator'] = [
      '#title' => $this->t('Separator'),
      '#type' => 'select',
      '#options' => [
        'space' => $this->getHumanFriendlyLabel('space'),
        'hyphen' => $this->getHumanFriendlyLabel('hyphen'),
        'comma' => $this->getHumanFriendlyLabel('comma'),
        'newline' => $this->getHumanFriendlyLabel('newline'),
      ] + $custom_separator_mappings,
      '#default_value' => $this->getSetting('separator'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#markup' => $this->durationService->getHumanReadableStringFromDateInterval(
          $item->get('duration')->getCastedValue(),
          $this->granularityService->convertGranularityStringToGranularityArray($this->getFieldSetting('granularity')),
          $this->getSeparator(),
          $this->getSetting('text_length')
        ),
      ];
    }

    return $element;
  }

  /**
   * Converts a key to a human readable value.
   *
   * @param string $key
   *   The machine readable name to be converted.
   * @param bool $capitalize
   *   Whether or not the return value should be capitalized.
   *
   * @return string
   *   The converted value, if a mapping exists, otherwise the original key
   */
  protected function getHumanFriendlyLabel($key, $capitalize = TRUE) {

    $custom_labels = $this->moduleHandler->invokeAll('duration_field_labels');
    if (!isset($custom_labels['capitalized'])) {
      $custom_labels['capitalized'] = [];
    }

    if (!isset($custom_labels['lowercase'])) {
      $custom_labels['lowercase'] = [];
    }

    if ($capitalize) {
      $values = [
        'full' => $this->t('Full'),
        'short' => $this->t('Short'),
        'space' => $this->t('Spaces'),
        'hyphen' => $this->t('Hyphens'),
        'comma' => $this->t('Commas'),
        'newline' => $this->t('New lines'),
      ] + $custom_labels['capitalized'];
    }
    else {
      $values = [
        'full' => $this->t('full'),
        'short' => $this->t('short'),
        'space' => $this->t('spaces'),
        'hyphen' => $this->t('hyphens'),
        'comma' => $this->t('commas'),
        'newline' => $this->t('new lines'),
      ] + $custom_labels['lowercase'];
    }

    return isset($values[$key]) ? $values[$key] : $key;
  }

  /**
   * Converts the key for a separator between values.
   *
   * @return string
   *   The value to be inserted between returned elements
   */
  protected function getSeparator() {

    $custom_separators = $this->moduleHandler->invokeAll('duration_field_separators');

    $separators = [
      'space' => ' ',
      'hyphen' => ' - ',
      'comma' => ', ',
      'newline' => '<br />',
    ] + $custom_separators;

    return $separators[$this->getSetting('separator')];
  }

}
