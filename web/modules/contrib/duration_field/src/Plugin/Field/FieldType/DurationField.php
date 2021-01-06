<?php

namespace Drupal\duration_field\Plugin\Field\FieldType;

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
 *   default_formatter = "duration_human_display",
 *   default_widget = "duration_widget",
 * )
 */
class DurationField extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'granularity' => 'y:m:d:h:i:s',
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

    return $properties;
  }

}
