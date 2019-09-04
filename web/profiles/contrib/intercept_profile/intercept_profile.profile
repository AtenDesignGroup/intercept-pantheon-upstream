<?php

/**
 * @file
 * Functions to alter the install profile form.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_form_alter().
 */
function intercept_profile_form_install_configure_form_alter(&$form, FormStateInterface $form_state) {

  // Account information defaults.
  $form['admin_account']['account']['name']['#default_value'] = 'administrator';

  // Date/time settings.
  $form['regional_settings']['site_default_country']['#default_value'] = 'US';

}
