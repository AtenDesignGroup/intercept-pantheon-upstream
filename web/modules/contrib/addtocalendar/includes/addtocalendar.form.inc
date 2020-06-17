<?php

/**
 * @file
 * Contains form for addtocalendar module.
 */

/**
 * Creates Settings form.
 *
 * @param $settings
 *   Third party Settings array.
 * @param $field_definition
 *   Field Definition of current field.
 *
 * @return array
 */
function _addtocalendar_build_form($settings, $field_definition) {
  $element = [];

  $element['addtocalendar_show'] = [
    '#type' => 'checkbox',
    '#title' => t('Show Add to Calendar'),
    '#default_value' => !empty($settings['addtocalendar_show']) ? $settings['addtocalendar_show'] : 0,
  ];

  $element['addtocalendar_settings'] = [
    '#type' => 'fieldset',
    '#title' => t('Add to Calendar Settings'),
    '#states' => [
      'visible' => [
        'input[name*="addtocalendar_show"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $element['addtocalendar_settings']['style'] = [
    '#type' => 'select',
    '#title' => t('Select Style'),
    '#options' => [
      0 => t('No Styling'),
      'blue' => t('Blue'),
      'glow_orange' => t('Glow Orange'),
    ],
    '#default_value' => !empty($settings['addtocalendar_settings']['style']) ? $settings['addtocalendar_settings']['style'] : 0,
  ];

  $element['addtocalendar_settings']['display_text'] = [
    '#type' => 'textfield',
    '#title' => t('Display Text'),
    '#default_value' => !empty($settings['addtocalendar_settings']['display_text']) ? $settings['addtocalendar_settings']['display_text'] : t('Add to Calendar'),
  ];

  // Fetching site name and site email id.
  $config = \Drupal::config('system.site');
  $site_name = $config->get('name');
  $site_mail = $config->get('mail');

  $info = [
    'atc_title' => [
      'label' => t('Title'),
      'sample_data' => 'Title',
    ],
    'atc_description' => [
      'label' => t('Description'),
      'sample_data' => 'Description',
    ],
    'atc_location' => [
      'label' => t('Location'),
      'sample_data' => 'Location',
    ],
    'atc_organizer' => [
      'label' => t('Organizer'),
      'sample_data' => $site_name,
    ],
    'atc_organizer_email' => [
      'label' => t('Organizer'),
      'sample_data' => $site_mail,
    ],
    'atc_date_start' => [
      'label' => t('Start Date'),
      'sample_data' => '',
    ],
    'atc_date_end' => [
      'label' => t('End Date'),
      'sample_data' => '',
    ],
  ];

  if ($field_definition instanceof Drupal\Core\Field\BaseFieldDefinition) {
    $field_definition = $field_definition->getConfig($field_definition->getTargetBundle());
  }

  $fields = Drupal::service('entity_field.manager')->getFieldDefinitions($field_definition->get('entity_type'), $field_definition->get('bundle'));
  $field_options = [];
  foreach ($fields as $id => $field) {
    if (is_a($field, 'Drupal\field\Entity\FieldConfig')) {
      $field_options[$id] = $field->getLabel();
    }
  }

  $field_options = array_merge(
    ['token' => t('Use Token/Static Content')],
    ['title' => t('Title')],
    $field_options
  );

  if (\Drupal::moduleHandler()->moduleExists('token')) {
    $element['addtocalendar_settings']['token_help'] = [
      '#type' => 'markup',
      '#token_types' => ['node'],
      '#theme' => 'token_tree_link',
    ];
  }

  foreach ($info as $id => $data) {
    $element['addtocalendar_settings'][$id]['field'] = [
      '#type' => 'select',
      '#title' => $data['label'] . t(': field'),
      '#options' => $field_options,
      '#description' => t('Select field to be used as @label for calendar events', ['@label' => $data['label']]),
      '#default_value' => !empty($settings['addtocalendar_settings'][$id]['field']) ? $settings['addtocalendar_settings'][$id]['field'] : '',
    ];
    $element['addtocalendar_settings'][$id]['tokenized'] = [
      '#type' => 'textfield',
      '#title' => $data['label'] . t(': Static/Tokenized Content'),
      '#default_value' => !empty($settings['addtocalendar_settings'][$id]['tokenized']) ? $settings['addtocalendar_settings'][$id]['tokenized'] : $data['sample_data'],
    ];
  }

  $element['addtocalendar_settings']['atc_privacy'] = [
    '#type' => 'select',
    '#title' => t('Privacy'),
    '#options' => [
      'public' => t('Public'),
      'private' => t('Private'),
    ],
    '#description' => t('Use public for free access to event information from any places. User private if the event is closed to public access.'),
    '#default_value' => !empty($settings['addtocalendar_settings']['atc_privacy']) ? $settings['addtocalendar_settings']['atc_privacy'] : 'public',
  ];
  $element['addtocalendar_settings']['data_secure'] = [
    '#type' => 'select',
    '#title' => t('Security level'),
    '#options' => [
      'auto' => t('Auto'),
      'true' => t('Use https only'),
      'false' => t('Use http only'),
    ],
    '#default_value' => !empty($settings['addtocalendar_settings']['data_secure']) ? $settings['addtocalendar_settings']['data_secure'] : 'auto',
  ];
  $element['addtocalendar_settings']['data_calendars'] = [
    '#type' => 'checkboxes',
    '#options' => [
      'iCalendar' => t('iCalendar'),
      'Google Calendar' => t('Google Calendar'),
      'Outlook' => t('Outlook'),
      'Outlook Online' => t('Outlook Online'),
      'Yahoo! Calendar' => t('Yahoo! Calendar'),
    ],
    '#default_value' => !empty($settings['addtocalendar_settings']['data_calendars']) ? $settings['addtocalendar_settings']['data_calendars'] : [],
    '#title' => t('List of calendars to show in button list.'),
  ];

  $cardinality = $field_definition->getFieldStorageDefinition()->getCardinality();

  if ($cardinality != 1) {
    $element['addtocalendar_settings']['multiple_value'] = [
      '#type' => 'select',
      '#title' => t('AddToCalendar button visibility'),
      '#options' => [
        1 => t('Single'),
        2 => t('All'),
      ],
      '#default_value' => !empty($settings['addtocalendar_settings']['multiple_value']) ? $settings['addtocalendar_settings']['multiple_value'] : 1,
    ];
    $element['addtocalendar_settings']['delta'] = [
      '#type' => 'textfield',
      '#title' => t('Delta'),
      '#description' => 'AddToCalendar button visibility for a particular date field (default starts with 0).',
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $field_definition->get('field_name') . '][settings_edit_form][third_party_settings][addtocalendar][addtocalendar_settings][multiple_value]"]' => [
            'value' => 1,
          ],
        ],
      ],
      '#default_value' => !empty($settings['addtocalendar_settings']['delta']) ? $settings['addtocalendar_settings']['delta'] : 0,
    ];
  }
  return $element;
}
