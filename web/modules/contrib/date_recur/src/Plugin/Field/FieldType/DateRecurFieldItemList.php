<?php

declare(strict_types = 1);

namespace Drupal\date_recur\Plugin\Field\FieldType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\date_recur\DateRecurPartGrid;
use Drupal\date_recur\Event\DateRecurEvents;
use Drupal\date_recur\Event\DateRecurValueEvent;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeFieldItemList;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Recurring date field item list.
 */
class DateRecurFieldItemList extends DateRangeFieldItemList {

  /**
   * Value for 'default_time_zone_source' to get time zone from a fixed string.
   */
  public const DEFAULT_TIME_ZONE_SOURCE_FIXED = 'fixed';

  /**
   * Value for 'default_time_zone_source' to get current users time zone.
   */
  public const DEFAULT_TIME_ZONE_SOURCE_CURRENT_USER = 'current_user';

  /**
   * An event dispatcher, primarily for unit testing purposes.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|null
   */
  protected ?EventDispatcherInterface $eventDispatcher = NULL;

  /**
   * {@inheritdoc}
   */
  public function postSave($update): bool {
    parent::postSave($update);
    $event = new DateRecurValueEvent($this, !$update);
    $this->getDispatcher()->dispatch($event, DateRecurEvents::FIELD_VALUE_SAVE);
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(): void {
    parent::delete();
    $event = new DateRecurValueEvent($this, FALSE);
    $this->getDispatcher()->dispatch($event, DateRecurEvents::FIELD_ENTITY_DELETE);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision(): void {
    parent::deleteRevision();
    $event = new DateRecurValueEvent($this, FALSE);
    $this->getDispatcher()->dispatch($event, DateRecurEvents::FIELD_REVISION_DELETE);
  }

  /**
   * Get the event dispatcher.
   *
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   *   The event dispatcher.
   */
  protected function getDispatcher(): EventDispatcherInterface {
    if (isset($this->eventDispatcher)) {
      return $this->eventDispatcher;
    }
    return \Drupal::service('event_dispatcher');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, FormStateInterface $form_state): array {
    $element = parent::defaultValuesForm($form, $form_state);

    $defaultValue = $this->getFieldDefinition()->getDefaultValueLiteral();

    $element['default_date_time_zone'] = [
      '#type' => 'select',
      '#title' => $this->t('Start and end date time zone'),
      '#description' => $this->t('Time zone is required if a default start date or end date is provided.'),
      '#options' => $this->getTimeZoneOptions(),
      '#default_value' => $defaultValue[0]['default_date_time_zone'] ?? '',
      '#states' => [
        // Show the field if either start or end is set.
        'invisible' => [
          [
            ':input[name="default_value_input[default_date_type]"]' => ['value' => ''],
            ':input[name="default_value_input[default_end_date_type]"]' => ['value' => ''],
          ],
        ],
      ],
    ];

    $defaultTimeZoneSource = $defaultValue[0]['default_time_zone_source'] ?? NULL;
    $defaultTimeZone = $defaultTimeZoneSource === 'current_user'
      ? 'current_user'
      : $defaultValue[0]['default_time_zone'] ?? '';

    $element['default_time_zone'] = [
      '#type' => 'select',
      '#title' => $this->t('Time zone'),
      '#description' => $this->t('Default time zone.'),
      '#options' => [
        'current_user' => $this->t('- Current user time zone -'),
      ] + $this->getTimeZoneOptions(),
      '#default_value' => $defaultTimeZone,
      '#empty_option' => $this->t('- None -'),
    ];

    $element['default_rrule'] = [
      '#type' => 'textarea',
      '#title' => $this->t('RRULE'),
      '#default_value' => $defaultValue[0]['default_rrule'] ?? '',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormValidate(array $element, array &$form, FormStateInterface $form_state): void {
    /** @var string|null $defaultDateTimeZone */
    $defaultDateTimeZone = $form_state->getValue(['default_value_input', 'default_date_time_zone']);
    if ($defaultDateTimeZone === NULL || strlen($defaultDateTimeZone) === '') {
      /** @var string|null $defaultStartType */
      $defaultStartType = $form_state->getValue(['default_value_input', 'default_date_type']);
      if ($defaultStartType !== NULL && strlen($defaultStartType) > 0) {
        $form_state->setErrorByName('default_value_input][default_date_time_zone', (string) $this->t('Time zone must be provided if a default start date is provided.'));
      }

      /** @var string|null $defaultEndType */
      $defaultEndType = $form_state->getValue(['default_value_input', 'default_end_date_type']);
      if ($defaultEndType !== NULL && strlen($defaultEndType) > 0) {
        $form_state->setErrorByName('default_value_input][default_date_time_zone', (string) $this->t('Time zone must be provided if a default end date is provided.'));
      }
    }

    parent::defaultValuesFormValidate($element, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state): array {
    $values = parent::defaultValuesFormSubmit($element, $form, $form_state);

    /** @var string|null $rrule */
    $rrule = $form_state->getValue(['default_value_input', 'default_rrule']);
    if ($rrule !== NULL && strlen($rrule) > 0) {
      $values[0]['default_rrule'] = $rrule;
    }

    /** @var string|null $defaultDateTimeZone */
    $defaultDateTimeZone = $form_state->getValue(['default_value_input', 'default_date_time_zone']);
    if ($defaultDateTimeZone !== NULL && strlen($defaultDateTimeZone) > 0) {
      $values[0]['default_date_time_zone'] = $defaultDateTimeZone;
    }

    /** @var string|null $defaultTimeZone */
    $defaultTimeZone = $form_state->getValue(['default_value_input', 'default_time_zone']);
    if ($defaultTimeZone !== NULL && strlen($defaultTimeZone) > 0) {
      if ($defaultTimeZone === 'current_user') {
        $values[0]['default_time_zone_source'] = 'current_user';
      }
      else {
        $values[0]['default_time_zone_source'] = 'fixed';
        $values[0]['default_time_zone'] = $defaultTimeZone;
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, FieldableEntityInterface $entity, FieldDefinitionInterface $definition): array {
    assert(is_array($default_value));
    $defaultDateTimeZone = $default_value[0]['default_date_time_zone'] ?? NULL;

    $defaultValue = FieldItemList::processDefaultValue($default_value, $entity, $definition);

    $defaultValues = [[]];

    $hasDefaultStartDateType = isset($defaultValue[0]['default_date_type']) && is_string($defaultValue[0]['default_date_type']) && strlen($defaultValue[0]['default_date_type']) > 0;
    $hasDefaultEndDateType = isset($defaultValue[0]['default_end_date_type']) && is_string($defaultValue[0]['default_end_date_type']) && strlen($defaultValue[0]['default_end_date_type']) > 0;
    $hasDefaultDateTimeZone = is_string($defaultDateTimeZone) && strlen($defaultDateTimeZone) > 0;
    if ($hasDefaultDateTimeZone && ($hasDefaultStartDateType || $hasDefaultEndDateType)) {
      $storageFormat = $definition->getSetting('datetime_type') == DateRecurItem::DATETIME_TYPE_DATE ? DateRecurItem::DATE_STORAGE_FORMAT : DateRecurItem::DATETIME_STORAGE_FORMAT;

      // Instruct 'value' and 'end_value' to convert from the localised time
      // zone to UTC.
      $formatSettings = ['timezone' => DateTimeItemInterface::STORAGE_TIMEZONE];

      if ($hasDefaultStartDateType) {
        $start_date = new DrupalDateTime($defaultValue[0]['default_date'], $defaultDateTimeZone);
        $start_value = $start_date->format($storageFormat, $formatSettings);
        $defaultValues[0]['value'] = $start_value;
        $defaultValues[0]['start_date'] = $start_date;
      }

      if ($hasDefaultEndDateType) {
        $end_date = new DrupalDateTime($defaultValue[0]['default_end_date'], $defaultDateTimeZone);
        $end_value = $end_date->format($storageFormat, $formatSettings);
        $defaultValues[0]['end_value'] = $end_value;
        $defaultValues[0]['end_date'] = $end_date;
      }

      $defaultValue = $defaultValues;
    }

    $rrule = $default_value[0]['default_rrule'] ?? NULL;
    if (is_string($rrule) && strlen($rrule) > 0) {
      $defaultValue[0]['rrule'] = $rrule;
    }

    $timeZoneSource = $default_value[0]['default_time_zone_source'] ?? NULL;
    if ($timeZoneSource === static::DEFAULT_TIME_ZONE_SOURCE_FIXED) {
      $defaultValue[0]['timezone'] = $default_value[0]['default_time_zone'];
    }
    elseif ($timeZoneSource === static::DEFAULT_TIME_ZONE_SOURCE_CURRENT_USER) {
      $timeZone = \date_default_timezone_get();
      if (strlen($timeZone) === 0) {
        throw new \Exception('Something went wrong. User has no time zone.');
      }
      $defaultValue[0]['timezone'] = $timeZone;
    }

    unset($defaultValue[0]['default_time_zone']);
    unset($defaultValue[0]['default_time_zone_source']);
    unset($defaultValue[0]['default_rrule']);
    return $defaultValue;
  }

  /**
   * Get a list of time zones suitable for a select field.
   *
   * @return array
   *   A list of time zones where keys are PHP time zone codes, and values are
   *   human readable and translatable labels.
   */
  protected function getTimeZoneOptions(): array {
    return \system_time_zones(TRUE, TRUE);
  }

  /**
   * Set the event dispatcher.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void {
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * Get the parts grid for this field.
   *
   * @return \Drupal\date_recur\DateRecurPartGrid
   *   The parts grid for this field.
   */
  public function getPartGrid(): DateRecurPartGrid {
    /** @var array{all: bool, frequencies: array<mixed>}|null $partSettings */
    $partSettings = $this->getFieldDefinition()->getSetting('parts');
    // Existing field configs may not have a parts setting yet.
    $partSettings ??= [];
    return DateRecurPartGrid::configSettingsToGrid($partSettings);
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($delta): void {
    foreach ($this->list as $item) {
      assert($item instanceof DateRecurItem);
      $item->resetHelper();
    }
    parent::onChange($delta);
  }

}
