<?php

namespace Drupal\duration_field\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
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
    DurationServiceInterface $duration_service,
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
    $duration = $values['duration'] ?? FALSE;
    $weeks = $values['weeks'] ?? 0;

    // We need to extract the weeks from the duration and seconds.
    if ($weeks) {
      $duration = new \DateInterval($duration);
      $duration = $this->durationService->removeWeeksFromDateInterval($duration, $weeks);
      $duration = $this->durationService->getDurationStringFromDateInterval($duration);
    }

    $element['duration'] = $element + [
      '#type' => 'duration',
      '#default_value' => $duration,
      '#description' => $element['#description'],
      '#cardinality' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
      '#granularity' => $this->getFieldSetting('granularity'),
      '#weeks_default_value' => $weeks,
      '#include_weeks' => $this->getFieldSetting('include_weeks'),
      '#after_build' => $this->getFieldSetting('include_weeks')
        ? [[__CLASS__, 'durationElementAfterBuild']]
        : [],
    ];

    return $element;
  }

  /**
   * After build callback for the duration element.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form element.
   */
  public static function durationElementAfterBuild(array $element, FormStateInterface $form_state) {
    // Check for existing date elements to pick weight.
    $weight = 0;
    if (!empty($element['d'])) {
      $weight = $element['d']['#weight'] - 0.5;
    }
    elseif (!empty($element['m'])) {
      $weight = $element['m']['#weight'] + 0.5;
    }
    elseif (!empty($element['y'])) {
      $weight = $element['y']['#weight'] + 0.5;
    }

    // Add weeks textfield to duration element.
    $parents = $element['#parents'];
    array_pop($parents);
    $parents[] = 'weeks';
    $element['weeks'] = [
      '#type' => 'number',
      '#title' => t('Weeks'),
      '#value' => $element['#weeks_default_value'],
      '#min' => 0,
      '#weight' => $weight,
      '#parents' => $parents,
      '#name' => str_replace('[duration]', '[weeks]', $element['#name']),
      '#element_validate' => [[static::class, 'formWeeksElementValidate']],
    ];

    // Adjust step to support weeks as well.
    if (!empty($element['#date_increment']) && (int) $element['#date_increment'] > 604800) {
      $element['weeks']['#step'] = (int) ($element['#date_increment'] / 604800);
      if (!empty($element['d'])) {
        unset($element['d']['#step']);
      }
    }
    return $element;
  }

  /**
   * Validation handler, sets the number of weeks.
   */
  public static function formWeeksElementValidate(array &$element, FormStateInterface $form_state) {
    // Get the submitted weeks into values.
    $input = $form_state->getUserInput();
    $form_state->setValueForElement($element, NestedArray::getValue($input, $element['#parents']));
  }

}
