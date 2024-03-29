<?php

namespace Drupal\intercept_core\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;
use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeWidgetBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'daterange_time_select' widget.
 *
 * @FieldWidget(
 *   id = "intercept_daterange_time_select",
 *   label = @Translation("Datetime Select"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class DateRangeTimeSelectWidget extends DateRangeWidgetBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'time_type' => '24',
    ] + parent::defaultSettings();
  }

  /**
   * The date format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityStorageInterface $date_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->dateStorage = $date_storage;
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
      $container->get('entity_type.manager')->getStorage('date_format')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Identify the type of date and time elements to use.
    switch ($this->getFieldSetting('datetime_type')) {
      case DateRangeItem::DATETIME_TYPE_DATE:
      case DateRangeItem::DATETIME_TYPE_ALLDAY:
        $date_type = 'date';
        $time_type = 'none';
        $date_format = $this->dateStorage->load('html_date')->getPattern();
        $time_format = '';
        break;

      default:
        $date_type = 'date';
        $time_type = 'time';
        $date_format = $this->dateStorage->load('html_date')->getPattern();
        $time_format = $this->getSetting('time_type') == '24' ? 'H:i' : 'g:i a';
        break;
    }

    $element['value'] += [
      '#date_date_format' => $date_format,
      '#date_date_element' => $date_type,
      '#date_date_callbacks' => [],
      '#date_time_format' => $time_format,
      '#date_time_element' => $time_type,
      '#date_time_callbacks' => [],
    ];

    $element['end_value'] += [
      '#date_date_format' => $date_format,
      '#date_date_element' => $date_type,
      '#date_date_callbacks' => [],
      '#date_time_format' => $time_format,
      '#date_time_element' => $time_type,
      '#date_time_callbacks' => [],
    ];

    $element['value']['#type'] = 'datetime';
    $element['end_value']['#type'] = 'datetime';
    // Use 15 minute increments.
    $increment = 15;
    $element['value']['#date_increment'] = $increment * 60;
    $element['end_value']['#date_increment'] = $increment * 60;
    // Round to the nearest 15 minutes and 0 seconds for default values.
    static::incrementRound($element['value']['#default_value'], $increment);
    static::incrementRound($element['end_value']['#default_value'], $increment);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['time_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Time type'),
      '#default_value' => $this->getSetting('time_type'),
      '#options' => ['24' => $this->t('24 hour time'), '12' => $this->t('12 hour time')],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Time type: @time_type', ['@time_type' => $this->getSetting('time_type')]);

    return $summary;
  }

  /**
   * Rounds minutes to nearest requested value.
   *
   * @param $date
   * @param $increment
   *
   * @return
   */
  protected static function incrementRound(&$date, $increment) {
    // Round minutes, if necessary.
    if ($date instanceof DrupalDateTime && $increment > 1) {
      $day = intval($date->format('j'));
      $hour = intval($date->format('H'));
      $second = 0;
      $minute = intval($date->format('i'));
      $minute = intval(round($minute / $increment) * $increment);
      if ($minute == 60) {
        $hour += 1;
        $minute = 0;
      }
      $date->setTime($hour, $minute, $second);
      if ($hour == 24) {
        $day += 1;
        $year = $date->format('Y');
        $month = $date->format('n');
        $date->setDate($year, $month, $day);
      }
    }
    return $date;
  }

}
