<?php

namespace Drupal\intercept_core\Plugin\Field\FieldFormatter;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'operations' formatter.
 *
 * @FieldFormatter(
 *   id = "operations_buttons",
 *   label = @Translation("Operations buttons"),
 *   field_types = {
 *     "operations",
 *   }
 * )
 */
class OperationsButtonsFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new TimestampFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * Gets the available format options.
   *
   * @return array|string
   *   A list of output formats. Each entry is keyed by the machine name of the
   *   format. The value is an array, of which the first item is the result for
   *   boolean TRUE, the second is for boolean FALSE. The value can be also an
   *   array, but this is just the case for the custom format.
   */
  protected function getLinkRendererOptions() {
    $formats = [
      'default' => $this->t('Default'),
      'ajax-modal' => $this->t('Modal Dialog (Ajax)'),
      'ajax-offcanvas' => $this->t('Off-canvas Dialog (Ajax)'),
    ];

    return $formats;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'link_renderer' => 'default',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['link_renderer'] = [
      '#title' => t('Link render type'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('link_renderer'),
      '#options' => $this->getLinkRendererOptions(),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $options = $this->getLinkRendererOptions();
    $summary[] = $options[$this->getSetting('link_renderer')];
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entity = $items->getEntity();
    $entity_type_id = $entity->getEntityTypeId();
    $operations = $this->entityTypeManager->getListBuilder($entity_type_id)->getOperations($entity);
    $operations = array_map(function ($operation) {
      $operation['attributes']['class'][] = 'button';
      switch ($this->getSetting('link_renderer')) {
        case 'ajax-modal':
          $operation['attributes']['class'][] = 'use-ajax';
          $operation['attributes']['data-dialog-type'] = 'modal';
          $operation['attributes']['data-dialog-options'] = Json::encode([
            'width' => 700,
          ]);
          $operations['#attached']['library'][] = 'core/drupal.dialog.ajax';
          break;

        case 'ajax-offcanvas':
          $operation['attributes']['class'][] = 'use-ajax';
          $operation['attributes']['data-dialog-type'] = 'dialog';
          $operation['attributes']['data-dialog-renderer'] = 'off_canvas';
          $operations['#attached']['library'][] = 'core/drupal.dialog.off_canvas';
          break;
      }
      return $operation;
    }, $operations);
    return [
      '#theme' => 'links__buttons',
      '#links' => $operations,
    ];
  }

}
