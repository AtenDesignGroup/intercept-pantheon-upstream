<?php

/**
 * @file
 * Builds placeholder replacement tokens for webform access type.
 */

use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\webform_access\Hook\WebformAccessTokensHooks;
use Drupal\Core\Render\BubbleableMetadata;

// phpcs:disable Drupal.Commenting.InlineComment.InvalidEndChar

/**
 * Implements hook_token_info().
 */
#[LegacyHook]
function webform_access_token_info() {
  return \Drupal::service(WebformAccessTokensHooks::class)->tokenInfo();
}

/**
 * Implements hook_tokens().
 */
#[LegacyHook]
function webform_access_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  return \Drupal::service(WebformAccessTokensHooks::class)->tokens($type, $tokens, $data, $options, $bubbleable_metadata);
}

/**
 * Get webform access group administrator or user email addresses.
 *
 * @param string $type
 *   The type of user (admin or user).
 * @param array $group_ids
 *   An array of webform access group ids.
 *
 * @return string
 *   Administrator or user email addresses.
 *
 * @internal
 */
function _webform_access_tokens_get_access_group_emails($type, array $group_ids) {
  $query = \Drupal::database()->select('webform_access_group_' . $type, 'gu');
  $query->condition('gu.group_id', $group_ids, 'IN');
  $query->join('users_field_data', 'u', 'u.uid = gu.uid');
  $query->fields('u', ['mail']);
  $query->condition('u.status', 1);
  $query->condition('u.mail', '', '<>');
  $query->orderBy('mail');
  $query->distinct();
  return $query->execute()->fetchCol();
}
