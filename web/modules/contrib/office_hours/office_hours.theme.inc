<?php

/**
 * @file
 * Implements preprocess hooks and theme hooks.
 *
 * Note: Hook preprocess_field must remain procedural (message in D11.1).
 * in Drupal\Core\Hook\HookCollectorPass::checkForProceduralOnlyHooks().
 */

use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\office_hours\Hook\OfficeHoursThemeHooks;

/**
 * Implements hook_preprocess_field().
 *
 * Note: Hook preprocess_field must remain procedural (message in D11.1).
 * in Drupal\Core\Hook\HookCollectorPass::checkForProceduralOnlyHooks().
 */
function office_hours_preprocess_field(&$variables, $hook) {
  return \Drupal::service(OfficeHoursThemeHooks::class)->preprocess_field($variables, $hook);
}

/**
 * Implements hook_preprocess_HOOK().
 *
 * Note: Hook preprocess_field must remain procedural (message in D11.1).
 * in Drupal\Core\Hook\HookCollectorPass::checkForProceduralOnlyHooks().
 */
function office_hours_preprocess_office_hours(&$variables) {
  return \Drupal::service(OfficeHoursThemeHooks::class)->preprocess_office_hours($variables);
}

/**
 * Implements hook_preprocess_HOOK().
 *
 * Note: Hook preprocess_field must remain procedural (message in D11.1).
 * in Drupal\Core\Hook\HookCollectorPass::checkForProceduralOnlyHooks().
 */
function office_hours_preprocess_office_hours_status(&$variables) {
  return \Drupal::service(OfficeHoursThemeHooks::class)->preprocess_office_hours_status($variables);
}

/**
 * Implements hook_preprocess_HOOK().
 *
 * Note: Hook preprocess_field must remain procedural (message in D11.1).
 * in Drupal\Core\Hook\HookCollectorPass::checkForProceduralOnlyHooks().
 */
function office_hours_preprocess_office_hours_table(&$variables) {
  return \Drupal::service(OfficeHoursThemeHooks::class)->preprocess_office_hours_table($variables);
}

/**
 * Implements hook_theme().
 */
#[LegacyHook]
function office_hours_theme() {
  return \Drupal::service(OfficeHoursThemeHooks::class)->theme();
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
#[LegacyHook]
function office_hours_theme_suggestions_office_hours(array $variables) {
  return \Drupal::service(OfficeHoursThemeHooks::class)->theme_suggestions_office_hours($variables);
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function office_hours_theme_suggestions_office_hours_status(array $variables) {
  return \Drupal::service(OfficeHoursThemeHooks::class)->theme_suggestions_office_hours_status($variables);
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function office_hours_theme_suggestions_office_hours_table(array $variables) {
  return \Drupal::service(OfficeHoursThemeHooks::class)->theme_suggestions_office_hours_table($variables);
}
