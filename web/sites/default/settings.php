<?php

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';

/**
 * Include the Pantheon-specific settings file.
 *
 * n.b. The settings.pantheon.php file makes some changes
 *      that affect all envrionments that this site
 *      exists in.  Always include this file, even in
 *      a local development environment, to insure that
 *      the site settings remain consistent.
 */
include \Pantheon\Integrations\Assets::dir() . "/settings.pantheon.php";

/**
 * Place the config directory outside of the Drupal root.
 */
$settings['config_sync_directory'] = '../config/sync';

/**
 * The default list of directories that will be ignored by Drupal's file API.
 *
 * By default ignore node_modules and bower_components folders to avoid issues
 * with common frontend tools and recursive scanning of directories looking for
 * extensions.
 *
 * @see file_scan_directory()
 * @see \Drupal\Core\Extension\ExtensionDiscovery::scanDirectory()
 */
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

if (defined('PANTHEON_ENVIRONMENT') && isset($_ENV['PANTHEON_ENVIRONMENT']) && $_ENV['PANTHEON_ENVIRONMENT'] != 'lando') {
  // Keeps performance fast with JSON:API and MariaDB.
  // See https://www.drupal.org/project/drupal/issues/3022864#comment-13256190.
  $databases['default']['default']['init_commands']['optimizer_search_depth'] = 'SET SESSION optimizer_search_depth = 0';
}

if (isset($_ENV['PANTHEON_ENVIRONMENT']) && php_sapi_name() != 'cli') {
  // Drupal 8 Trusted Host Settings.
  if (is_array($settings)) {
    $settings['trusted_host_patterns'] = array('^'. preg_quote($primary_domain) . '$');
  }
}

// Turn error reporting of notices off on test and live.
// All Pantheon Environments.
if (isset($_ENV['PANTHEON_ENVIRONMENT'])) {
  if (in_array($_ENV['PANTHEON_ENVIRONMENT'], ['test', 'live'])) {
    ini_set('error_reporting', E_ALL & ~E_NOTICE);
  }
}

/**
 * If there is a local settings file, then include it.
 */
$local_settings = __DIR__ . "/settings.local.php";
if (file_exists($local_settings)) {
  include $local_settings;
}

if (isset($_ENV['PANTHEON_ENVIRONMENT'])) {
  $GLOBALS['conf']['container_service_providers']['PantheonServiceProvider'] = '\Pantheon\Internal\PantheonServiceProvider11';
}