<?php

/**
 * @file
 * Contains views API hooks for Flag module.
 */

use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\flag\Hook\FlagViewsHooks;

/**
 * Implements hook_views_data().
 */
#[LegacyHook]
function flag_views_data() {
  return \Drupal::service(FlagViewsHooks::class)->viewsData();
}

/**
 * Implements hook_views_data_alter().
 */
#[LegacyHook]
function flag_views_data_alter(array &$data) {
  \Drupal::service(FlagViewsHooks::class)->viewsDataAlter($data);
}
