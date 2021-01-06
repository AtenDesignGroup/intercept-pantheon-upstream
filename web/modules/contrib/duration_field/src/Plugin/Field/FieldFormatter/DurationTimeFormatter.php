<?php

namespace Drupal\duration_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\duration_field\Service\GranularityServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a time format formatter for the Duration field type.
 *
 * @FieldFormatter(
 *   id = "duration_time_display",
 *   label = @Translation("Time Format"),
 *   field_types = {
 *     "duration"
 *   }
 * )
 */
class DurationTimeFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The granularity service.
   *
   * @var \Drupal\duration_field\Service\GranularityServiceInterface
   */
  protected $granularityService;

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
   * @param \Drupal\duration_field\Service\GranularityServiceInterface $granularityService
   *   The granularity service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    $field_config,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    GranularityServiceInterface $granularityService
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_config, $settings, $label, $view_mode, $third_party_settings);

    $this->granularityService = $granularityService;
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
      $container->get('duration_field.granularity.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = [];

    $summary[] = $this->t('Displays the duration in the format: YY/MM/DD HH:MM:SS');
    $summary[] = $this->t('If only date components are part of the field granularity, time will not be shown');
    $summary[] = $this->t('If only time components are part of the field granularity, the date will not be shown.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {

      $element[$delta] = [
        '#theme' => 'duration_field_duration_time',
        '#item' => $item->get('duration')->getCastedValue(),
      ];
    }

    return $element;
  }

}
