<?php

/**
 * @file
 * Contains views API hooks in the views_execution group for Flag module.
 */

use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\flag\Hook\FlagViewsExecutionHooks;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_query_substitutions().
 */
#[LegacyHook]
function flag_views_query_substitutions(ViewExecutable $view) {
  return \Drupal::service(FlagViewsExecutionHooks::class)->viewsQuerySubstitutions($view);
}
