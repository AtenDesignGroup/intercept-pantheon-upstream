<?php

/**
 * @file
 * Builds placeholder replacement tokens for webform nodes.
 */

use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\webform_node\Hook\WebformNodeTokensHooks;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
#[LegacyHook]
function webform_node_token_info() {
  return \Drupal::service(WebformNodeTokensHooks::class)->tokenInfo();
}

/**
 * Implements hook_tokens().
 */
#[LegacyHook]
function webform_node_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  return \Drupal::service(WebformNodeTokensHooks::class)->tokens($type, $tokens, $data, $options, $bubbleable_metadata);
}
