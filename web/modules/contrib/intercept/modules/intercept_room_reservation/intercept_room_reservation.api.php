<?php

/**
 * @file
 * Hooks provided by the Intercept Room Reservation module.
 */

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows the list of reservation emails to be altered.
 *
 * @param array $emails
 *   The array of emails, structured as 'key' => 'label'.
 *
 * @ingroup intercept
 */
function hook_intercept_reservation_emails_alter(array &$emails) {
  $emails['rerequested'] = new TranslatableMarkup('Reservation rerequested');
}

/**
 * @} End of "addtogroup hooks".
 */
