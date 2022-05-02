<?php

namespace Drupal\intercept_event;

/**
 * Provides a method of invalidating the time-sensitive check-in periods of events.
 */
interface CheckinPeriodInvalidatorInterface {

  /**
   * Invalidates the cache of all event nodes whose checkin period statuses have changed
   * since the last run.
   *
   * @return int[]
   *   An array of node ids that were cleared.
   */
  public function updateCheckinPeriods(array $values);

  /**
   * Invalidates the cache of all event nodes whose checkin period statuses have changed
   * since the last run.
   *
   * @return int[]
   *   An array of node ids that were cleared.
   */
  public function invalidateCheckinPeriods();

  /**
   * Clears the last_run state of the invalidator.
   *
   * @return void
   */
  public function resetInvalidationPeriod();

}
