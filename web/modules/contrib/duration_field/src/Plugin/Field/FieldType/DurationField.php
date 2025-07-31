<?php

namespace Drupal\duration_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\duration_field\Plugin\DataType\Iso8601StringInterface;

/**
 * Provides the Duration field type.
 *
 * @FieldType(
 *   id = "duration",
 *   label = @Translation("Duration"),
 *   description = @Translation("Stores a duration of time, with configurable units such as hours, days, or weeks."),
 *   default_formatter = "duration_human_display",
 *   default_widget = "duration_widget",
 * )
 */
class DurationField extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return "duration";
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'granularity' => 'y:m:d:h:i:s',
      'include_weeks' => FALSE,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element['granularity'] = [
      '#type' => 'granularity',
      '#title' => $this->t('Granularity'),
      '#default_value' => $this->getSetting('granularity'),
    ];
    $element['include_weeks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include weeks'),
      '#default_value' => $this->getSetting('include_weeks'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        // The ISO 8601 Duration string representing the duration.
        'duration' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        // The number of seconds the duration represents. Allows for
        // mathematical comparison of durations in queries.
        'seconds' => [
          'type' => 'int',
          'size' => 'big',
        ],
        // The number of weeks to store alongside ISO 8601
        // duration string which does not support weeks.
        'weeks' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('duration')->getValue();

    return $value == Iso8601StringInterface::EMPTY_DURATION || is_null($value) || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['seconds'] = DataDefinition::create('integer')
      ->setLabel(t('Seconds'))
      ->setDescription(t('The number of seconds the duration represents'));

    $properties['duration'] = DataDefinition::create('php_date_interval')
      ->setLabel('Duration')
      ->setDescription(t('The PHP DateInterval object'));

    $properties['weeks'] = DataDefinition::create('integer')
      ->setLabel(t('Weeks'))
      ->setDescription(t('The number of weeks additional to ISO 8601 duration string.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $value = parent::getValue();

    // If we have weeks set then we need to adjust the duration string
    // and seconds to include the weeks duration.
    if (!empty($value['weeks'])) {
      $duration = new \DateInterval($value['duration']);
      $duration = \Drupal::service('duration_field.service')
        ->addWeeksToDateInterval($duration, $value['weeks']);
      $value['duration'] = \Drupal::service('duration_field.service')
        ->getDurationStringFromDateInterval($duration);
      $value['seconds'] = \Drupal::service('duration_field.service')
        ->getSecondsFromDateInterval($duration);
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();
    $duration_string = $this->get('duration')->getValue();
    if (!empty($duration_string)) {
      $seconds = \Drupal::service('duration_field.service')
        ->getSecondsFromDateInterval(new \DateInterval($duration_string));
      $this->set('seconds', $seconds);
    }
    else {
      $this->set('seconds', 0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $granularity = $field_definition->getSetting('granularity');
    $parts = explode(':', $granularity);

    $input = array_combine($parts, $parts);
    foreach ($input as $key) {
      $input[$key] = match($key) {
        'y' =>  rand(1, 10),
        'm' =>  rand(1, 12),
        'd' =>  rand(1, 30),
        'h' =>  rand(1, 12),
        'i' =>  rand(1, 60),
        's' =>  rand(1, 60),
        default => rand(1, 10),
      };
    }

    /** @var \Drupal\duration_field\Service\DurationServiceInterface $duration_service */
    $duration_service = \Drupal::service('duration_field.service');

    $duration = $duration_service->convertDateArrayToDateInterval($input);
    $seconds = $duration_service->getSecondsFromDateInterval($duration);

    return [
      'duration' => $duration,
      'seconds' => $seconds,
    ];
  }

}
