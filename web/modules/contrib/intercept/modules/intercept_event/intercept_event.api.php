<?php

/**
 * @file
 * Hooks provided by the Intercept Event module.
 */

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows the list of registration emails to be altered.
 *
 * @param array $emails
 *   The array of emails, structured as 'key' => 'label'.
 *
 * @ingroup intercept
 */
function hook_intercept_registration_emails_alter(array &$emails) {
  $emails['reactivated'] = new TranslatableMarkup('Registration reactivated');
}

/**
 * Allows the event registration access to be altered.
 *
 * @param \Drupal\node\NodeInterface $event
 *   The event that is being registered for.
 */
function hook_event_registration_event_create_access_alter(NodeInterface $event) {

}

/**
 * @} End of "addtogroup hooks".
 */
