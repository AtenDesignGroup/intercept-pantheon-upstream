<?php

namespace Drupal\office_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface;

/**
 * Abstract plugin implementation of the formatter.
 */
abstract class OfficeHoursFormatterBase extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'day_format' => 'long',
      'time_format' => 'G',
      'compress' => FALSE,
      'grouped' => FALSE,
      'show_closed' => 'all',
      'closed_format' => 'Closed',
      'separator' => [
        'days' => '<br />',
        'grouped_days' => ' - ',
        'day_hours' => ': ',
        'hours_hours' => '-',
        'more_hours' => ', ',
      ],
      'current_status' => [
        'position' => '', // Hidden.
        'open_text' => 'Currently open!',
        'closed_text' => 'Currently closed',
      ],
      'schema' => [
        'enabled' => FALSE,
      ],
      'timezone_field' => '',
      'office_hours_first_day' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $settings = $this->getSettings();
    $day_names = OfficeHoursDateHelper::weekDays(FALSE);
    $day_names[''] = $this->t("- system's Regional settings -");

    /*
    // Find timezone fields, to be used in 'Current status'-option.
    $fields = field_info_instances( (isset($form['#entity_type']) ? $form['#entity_type'] : NULL), (isset($form['#bundle']) ? $form['#bundle'] : NULL));
    $timezone_fields = [];
    foreach ($fields as $field_name => $timezone_instance) {
      if ($field_name == $field['field_name']) {
        continue;
      }
      $timezone_field = field_read_field($field_name);

      if (in_array($timezone_field['type'], ['tzfield'])) {
        $timezone_fields[$timezone_instance['field_name']] = $timezone_instance['label'] . ' (' . $timezone_instance['field_name'] . ')';
      }
    }
    if ($timezone_fields) {
      $timezone_fields = ['' => '<None>'] + $timezone_fields;
    }
     */

    $element['show_closed'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of days to show'),
      '#options' => [
        'all' => $this->t('Show all days'),
        'open' => $this->t('Show only open days'),
        'next' => $this->t('Show next open day'),
        'none' => $this->t('Hide all days'),
        'current' => $this->t('Show only current day'),
      ],
      '#default_value' => $settings['show_closed'],
      '#description' => $this->t('The days to show in the formatter. Useful in combination with the Current Status block.'),
    ];
    // First day of week, copied from system.variable.inc.
    $element['office_hours_first_day'] = [
      '#type' => 'select',
      '#options' => $day_names,
      '#title' => $this->t('First day of week'),
      '#default_value' => $this->getSetting('office_hours_first_day'),
    ];
    $element['day_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Day notation'),
      '#options' => [
        'long' => $this->t('long'),
        'short' => $this->t('3-letter weekday abbreviation'),
        'two_letter' => $this->t('2-letter weekday abbreviation'),
        'number' => $this->t('number'),
        'none' => $this->t('none'),
      ],
      '#default_value' => $settings['day_format'],
    ];
    // @todo D8 Align with DateTimeDatelistWidget.
    $element['time_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Time notation'),
      '#options' => [
        'G' => $this->t('24 hour time') . ' (9:00)', // D7: key = 0.
        'H' => $this->t('24 hour time') . ' (09:00)', // D7: key = 2.
        'g' => $this->t('12 hour time') . ' (9:00 am)', // D7: key = 1.
        'h' => $this->t('12 hour time') . ' (09:00 am)', // D7: key = 1.
      ],
      '#default_value' => $settings['time_format'],
      '#required' => FALSE,
      '#description' => $this->t('Format of the clock in the formatter.'),
    ];
    $element['compress'] = [
      '#title' => $this->t('Compress all hours of a day into one set'),
      '#type' => 'checkbox',
      '#default_value' => $settings['compress'],
      '#description' => $this->t('Even if more hours is allowed, you might want to show a compressed form. E.g., 7:00-12:00, 13:30-19:00 becomes 7:00-19:00.'),
      '#required' => FALSE,
    ];
    $element['grouped'] = [
      '#title' => $this->t('Group consecutive days with same hours into one set'),
      '#type' => 'checkbox',
      '#default_value' => $settings['grouped'],
      '#description' => $this->t('E.g., Mon: 7:00-19:00; Tue: 7:00-19:00 becomes Mon-Tue: 7:00-19:00.'),
      '#required' => FALSE,
    ];
    $element['closed_format'] = [
      '#type' => 'textfield',
      '#size' => 30,
      '#title' => $this->t('Empty days notation'),
      '#default_value' => $settings['closed_format'],
      '#required' => FALSE,
      '#description' => $this->t('Format of empty (closed) days. String
        <a>can be translated</a> when the
        <a href=":install">Interface Translation module</a> is installed.',
        [
          ':install' => Url::fromRoute('system.modules_list')->toString(),
        ]
      ),
    ];

    // Taken from views_plugin_row_fields.inc.
    $element['separator'] = [
      '#title' => $this->t('Separators'),
      '#type' => 'details',
      '#open' => FALSE,
    ];
    $element['separator']['days'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $settings['separator']['days'],
      '#description' => $this->t('This separator will be placed between the days. Use &#39&ltbr&gt&#39 to show each day on a new line.'),
    ];
    $element['separator']['grouped_days'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $settings['separator']['grouped_days'],
      '#description' => $this->t('This separator will be placed between the labels of grouped days.'),
    ];
    $element['separator']['day_hours'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $settings['separator']['day_hours'],
      '#description' => $this->t('This separator will be placed between the day and the hours.'),
    ];
    $element['separator']['hours_hours'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $settings['separator']['hours_hours'],
      '#description' => $this->t('This separator will be placed between the hours of a day.'),
    ];
    $element['separator']['more_hours'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $settings['separator']['more_hours'],
      '#description' => $this->t('This separator will be placed between the hours and more_hours of a day.'),
    ];

    // Show a 'Current status' option.
    $element['current_status'] = [
      '#title' => $this->t('Current status'),
      '#type' => 'details',
      '#open' => FALSE,
      '#description' => $this->t('Below strings <a>can be translated</a> when the
        <a href=":install">Interface Translation module</a> is installed.',
        [
          ':install' => Url::fromRoute('system.modules_list')->toString(),
        ]),
    ];
    $element['current_status']['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Current status position'),
      '#options' => [
        '' => $this->t('Hidden'),
        'before' => $this->t('Before hours'),
        'after' => $this->t('After hours'),
      ],
      '#default_value' => $settings['current_status']['position'],
      '#description' => $this->t('Where should the current status be located?'),
    ];
    $element['current_status']['open_text'] = [
      '#title' => $this->t('Status strings'),
      '#type' => 'textfield',
      '#size' => 40,
      '#default_value' => $settings['current_status']['open_text'],
      '#description' => $this->t('Format of the message displayed when currently open.'),
    ];
    $element['current_status']['closed_text'] = [
      '#type' => 'textfield',
      '#size' => 40,
      '#default_value' => $settings['current_status']['closed_text'],
      '#description' => $this->t('Format of message displayed when currently closed.'),
    ];

    $element['schema'] = [
      '#title' => $this->t('Schema.org openingHours support'),
      '#type' => 'details',
      '#open' => FALSE,
    ];
    $element['schema']['enabled'] = [
      '#title' => $this->t('Enable Schema.org openingHours support'),
      '#type' => 'checkbox',
      '#default_value' => $settings['schema']['enabled'],
      '#description' => $this->t('Enable meta tags with property for Schema.org openingHours.'),
      '#required' => FALSE,
    ];

    /*
    if ($timezone_fields) {
      $element['timezone_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Timezone') . ' ' . $this->t('Field'),
        '#options' => $timezone_fields,
        '#default_value' => $settings['timezone_field'],
        '#description' => $this->t('Should we use another field to set the timezone for these hours?'),
      ];
    }
    else {
      $element['timezone_field'] = [
        '#type' => 'hidden',
        '#value' => $settings['timezone_field'],
      ];
    }
     */

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    // @todo Return more info, like Date module does.
    $summary[] = $this->t('Display Office hours in different formats.');
    return $summary;
  }

  /**
   * Add an 'openingHours' formatter from https://schema.org/openingHours.
   *
   * @param \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items
   *   The office hours.
   * @param $langcode
   *   The required language code.
   * @param array $elements
   *   Elements.
   *
   * @return array
   *   A formatter element.
   */
  protected function addSchemaFormatter(OfficeHoursItemListInterface $items, $langcode, array $elements) {
    if (empty($this->settings['schema']['enabled'])) {
      return $elements;
    }

    $formatter = new OfficeHoursFormatterSchema(
      $this->pluginId, $this->pluginDefinition, $this->fieldDefinition,
      $this->settings, $this->viewMode, $this->label, $this->thirdPartySettings);

    $new_element = $formatter->viewElements($items, $langcode);
    $elements[] = $new_element[0];
    return $elements;
  }

  /**
   * Add a 'status' formatter before or after the hours, if necessary.
   *
   * @param \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $items
   *   The office hours.
   * @param $langcode
   *   The required language code.
   * @param array $elements
   *   Elements.
   *
   * @return array
   *   A formatter element.
   */
  protected function addStatusFormatter(OfficeHoursItemListInterface $items, $langcode, array $elements) {

    if (empty($this->settings['current_status']['position'])) {
      return $elements;
    }

    $formatter = new OfficeHoursFormatterStatus(
      $this->pluginId, $this->pluginDefinition, $this->fieldDefinition,
      $this->settings, $this->viewMode, $this->label, $this->thirdPartySettings);

    $new_element = $formatter->viewElements($items, $langcode);

    switch ($new_element['#position']) {
      case 'before':
        array_unshift($elements, $new_element);
        break;

      case'after':
        array_push($elements, $new_element);
        break;

      default:
        break;
    }

    return $elements;
  }

}
