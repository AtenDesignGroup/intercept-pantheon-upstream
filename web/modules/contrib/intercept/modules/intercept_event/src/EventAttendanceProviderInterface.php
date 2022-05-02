<?php

namespace Drupal\intercept_event;

use Drupal\Core\Session\AccountInterface;

/**
 * Provides event attendances.
 */
interface EventAttendanceProviderInterface {

  /**
   * Check if the attendance exists by field_event and field_user.
   *
   * @param int $nid
   *   Event node id.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. Defaults to the current user.
   *
   * @return bool|\Drupal\intercept_event\Entity\EventAttendanceInterface
   *   The Event Attendance entity, or FALSE.
   */
  public function getEventAttendance($nid, AccountInterface $account = NULL);

  /**
   * Gets all event attendance entities for an account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. Defaults to the current user.
   *
   * @return \Drupal\intercept_event\Entity\EventAttendanceInterface[]
   *   An array of event attendance entities.
   */
  public function getEventAttendances(AccountInterface $account = NULL);

  /**
   * Gets all event attendance ids for an account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. Defaults to the current user.
   *
   * @return int[]
   *   An array of event attendance ids.
   */
  public function getEventAttendanceIds(AccountInterface $account = NULL);

}
