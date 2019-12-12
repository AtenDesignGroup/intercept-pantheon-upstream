<?php

/**
 * @file
 * The settings file.
 */

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
 *      a local development environment, to ensure that
 *      the site settings remain consistent.
 */
include __DIR__ . "/settings.pantheon.php";

if (isset($databases['default']['default'])) {
  if (defined('PANTHEON_ENVIRONMENT')) {
    // Keeps performance fast with JSON:API and MariaDB.
    // See https://www.drupal.org/project/drupal/issues/3022864#comment-13256190.
    $databases['default']['default']['init_commands']['optimizer_search_depth'] = 'SET SESSION optimizer_search_depth = 0';
  }
}

/**
 * Place the config directory outside of the Drupal root.
 */
$config_directories = array(
  CONFIG_SYNC_DIRECTORY => dirname(DRUPAL_ROOT) . '/config',
);

/**
 * If there is a local settings file, then include it.
 */
$local_settings = __DIR__ . "/settings.local.php";
if (file_exists($local_settings)) {
  include $local_settings;
}

/**
 * See: tests/installer-features/installer.feature.
 */
$settings['install_profile'] = 'intercept_profile';
$databases['default']['default'] = array (
  'database' => 'intercept_upstream',
  'username' => 'drupaluser',
  'password' => '',
  'prefix' => '',
  'host' => '127.0.0.1',
  'port' => '33067',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);
$settings['hash_salt'] = 'VjVC-Xp_U9qYXQUhXhesnpb_pn6lycgeZSMIcMX5s1_zl3FWg8OnIbvn6MIPo281oW-XwhXYlg';
