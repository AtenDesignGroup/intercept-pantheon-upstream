<?php

/**
 * @file
 * Flag module tokens support.
 */

use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\flag\Hook\FlagTokensHooks;

/**
 * Implements hook_token_info().
 */
#[LegacyHook]
function flag_token_info() {
  return \Drupal::service(FlagTokensHooks::class)->tokenInfo();
}
