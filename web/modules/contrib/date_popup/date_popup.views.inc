<?php

/**
 * @file
 * Views hooks.
 */

use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\date_popup\Hook\DatePopupViewsHooks;

/**
 * Implements hook_views_plugins_filter_alter().
 */
#[LegacyHook]
function date_popup_views_plugins_filter_alter(&$info) {
  \Drupal::service(DatePopupViewsHooks::class)->viewsPluginsFilterAlter($info);
}
