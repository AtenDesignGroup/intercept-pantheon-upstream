<?php

namespace Drupal\intercept_event\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Event Attendance entities.
 *
 * @ingroup intercept_event
 */
interface EventAttendanceInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Event Attendance name.
   *
   * @return string
   *   Name of the Event Attendance.
   */
  public function getName();

  /**
   * Sets the Event Attendance name.
   *
   * @param string $name
   *   The Event Attendance name.
   *
   * @return \Drupal\intercept_event\Entity\EventAttendanceInterface
   *   The called Event Attendance entity.
   */
  public function setName($name);

  /**
   * Gets the Event Attendance creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Event Attendance.
   */
  public function getCreatedTime();

  /**
   * Sets the Event Attendance creation timestamp.
   *
   * @param int $timestamp
   *   The Event Attendance creation timestamp.
   *
   * @return \Drupal\intercept_event\Entity\EventAttendanceInterface
   *   The called Event Attendance entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Event Attendance published status indicator.
   *
   * Unpublished Event Attendance are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Event Attendance is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Event Attendance.
   *
   * @param bool $published
   *   TRUE to set this Event Attendance to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\intercept_event\Entity\EventAttendanceInterface
   *   The called Event Attendance entity.
   */
  public function setPublished($published);

}
