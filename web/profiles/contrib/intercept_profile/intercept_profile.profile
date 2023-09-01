<?php

/**
 * @file
 * Functions to alter the install profile form.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Implements hook_install_tasks().
 */
function intercept_profile_install_tasks(&$install_state) {
  return [
    'intercept_profile_site_setup' => [
      'display_name' => new TranslatableMarkup('Install demo content modules'),
      'type' => 'batch',
    ],
  ];
}

/**
 * Implements hook_form_FORM_ID_form_alter().
 */
function intercept_profile_form_install_configure_form_alter(&$form, FormStateInterface $form_state) {

  // Account information defaults.
  $form['admin_account']['account']['name']['#default_value'] = 'administrator';

  // Date/time settings.
  $form['regional_settings']['site_default_country']['#default_value'] = 'US';

  // Checkboxes to generate demo content.
  $form['demo_content'] = [
    '#type' => 'checkboxes',
    '#title' => new TranslatableMarkup('Generate demo content'),
    '#options' => [
      'intercept_profile_content' => new TranslatableMarkup('Locations, Rooms, and Events'),
      'intercept_profile_content_equipment' => new TranslatableMarkup('Equipment'),
    ],
  ];
  $form['#submit'][] = 'intercept_profile_submit';
}

/**
 * Submit handler.
 */
function intercept_profile_submit($form_id, &$form_state) {
  $demo_modules = array_filter($form_state->getValue('demo_content'));
  \Drupal::state()->set('intercept_profile_install_demo_content', $demo_modules);
}

/**
 * Intercept profile install task.
 *
 * @param array $install_state
 *   The install state.
 *
 * @return array
 *   Batch settings.
 */
function intercept_profile_site_setup(array &$install_state) {
  $batch = [];

  $social_optional_modules = \Drupal::state()->get('intercept_profile_install_demo_content');
  foreach ($social_optional_modules as $module => $module_name) {
    $batch['operations'][] = [
      '_intercept_profile_install_module_batch',
      [[$module], $module_name],
    ];
  }

  return $batch;
}

/**
 * Implements callback_batch_operation().
 *
 * Performs batch installation of modules.
 */
function _intercept_profile_install_module_batch($module, $module_name, &$context) {
  set_time_limit(0);

  \Drupal::service('module_installer')->install($module);
  $context['results'][] = $module;
  $context['message'] = new TranslatableMarkup('Install %module_name module.', ['%module_name' => $module_name]);
}
