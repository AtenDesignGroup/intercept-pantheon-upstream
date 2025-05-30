<?php

/**
 * @file
 * Charts highcharts post-update file.
 */

use Drupal\charts_highcharts\Plugin\chart\Library\Highcharts;

/**
 * @file
 * Charts highcharts post-update file.
 */

/**
 * Set default global options.
 */
function charts_highcharts_post_update_set_default_global_options(&$sandbox) {
  /** @var \Drupal\Core\Config\Config $config */
  $config = \Drupal::service('config.factory')->getEditable('charts.settings');
  $library_plugin_id = $config->get('charts_default_settings.library');
  $library_is_highcharts = $library_plugin_id === 'highcharts';
  $global_options_config_key = 'charts_default_settings.library_config.global_options';
  // Getting the current config key to ensure that we don't overwrite the
  // existing configurations in case the user saved the config before running
  // this update.
  $global_options = $library_is_highcharts ? $config->get($global_options_config_key) : [];
  if ($library_is_highcharts && !$global_options) {
    $config->set($global_options_config_key, Highcharts::defaultGlobalOptions());
    return 'Global options config were updated.';
  }
  return 'Global options update were not set because the default library is not highcharts or existing global options were saved before running post update.';
}

/**
 * Updates the Highcharts library selection from additional libraries added.
 */
function charts_highcharts_post_update_update_library_selection(&$sandbox) {
  /** @var \Drupal\Core\Config\Config $config */
  $config = \Drupal::service('config.factory')->getEditable('charts.settings');
  $library_plugin_id = $config->get('charts_default_settings.library');
  $library_is_highcharts = $library_plugin_id === 'highcharts';
  if ($library_is_highcharts) {
    $config->set('charts_default_settings.library_config.3d_library', TRUE);
    $config->set('charts_default_settings.library_config.accessibility_library', TRUE);
    $config->set('charts_default_settings.library_config.annotations_library', FALSE);
    $config->set('charts_default_settings.library_config.boost_library', FALSE);
    $config->set('charts_default_settings.library_config.data_library', FALSE);
    $config->save();
    return 'Library selection was updated to charts_highcharts.';
  }
  return 'Library selection was not updated because the default library is not highcharts.';
}
