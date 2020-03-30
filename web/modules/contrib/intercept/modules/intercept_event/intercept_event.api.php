<?php

/**
 * @file
 * Hooks provided by the Intercept Event module.
 */

use Drupal\Core\StringTranslation\TranslatableMarkup;

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
 * @} End of "addtogroup hooks".
 */
