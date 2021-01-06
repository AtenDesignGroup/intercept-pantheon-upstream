<?php

namespace Drupal\duration_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\duration_field\Service\DurationServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Widget for inputting durations.
 *
 * @FieldWidget(
 *   id = "duration_widget",
 *   label = @Translation("Duration widget"),
 *   field_types = {
 *     "duration"
 *   }
 * )
 */
class DurationWidget extends WidgetBase implements WidgetInterface, ContainerFactoryPluginInterface {

  /**
   * The Duration service.
   *
   * @var \Drupal\duration_field\Service\DurationServiceInterface
   */
  protected $durationService;

  /**
   * Constructs a DurationWidget object.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The field settings.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\duration_field\Service\DurationServiceInterface $duration_service
   *   The duration service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    DurationServiceInterface $duration_service
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->durationService = $duration_service;
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
      $configuration['third_party_settings'],
      $container->get('duration_field.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $values = $items[$delta]->getValue();
    $duration = isset($values['duration']) ? $values['duration'] : FALSE;
    $seconds = isset($values['seconds']) ? $values['seconds'] : 0;

    $element['duration'] = $element + [
      '#type' => 'duration',
      '#default_value' => $duration,
      '#description' => $element['#description'],
      '#cardinality' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
      '#granularity' => $this->getFieldSetting('granularity'),
    ];

    // Set a default value for seconds. This is over written in
    // ::formElementValidate based on the submitted values for date_interval.
    $element['seconds'] = [
      '#type' => 'value',
      '#value' => $seconds,
    ];

    // Add submit handler to validate a form element. Values for duration_string
    // and seconds will be inserted in this submit handler. These will become
    // the values saved for the field.
    $element['#element_validate'][] = [$this, 'formElementValidate'];

    return $element;
  }

  /**
   * Validation handler, sets the number of seconds for the submitted duration.
   */
  public function formElementValidate(array &$element, FormStateInterface $form_state) {
    // Get the submitted DateInterval.
    $date_interval = $form_state->getValue($element['duration']['#parents']);
    // Get the number of seconds for the duration.
    $seconds = $this->durationService->getSecondsFromDateInterval($date_interval);
    // Save the values to the form state.
    $form_state->setValueForElement($element['seconds'], $seconds);
  }

}
