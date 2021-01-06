<?php

namespace Drupal\intercept_event\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface for defining Event Attendance entities.
 *
 * @ingroup intercept_event
 */
interface EventAttendanceInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the number of Event attendees.
   *
   * @return int
   *   The total of event_attendees.
   */
  public function total();

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
   * Gets the Event entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The event entity.
   */
  public function getEvent();

  /**
   * Gets the Event ID.
   *
   * @return int
   *   The event entity ID.
   */
  public function getEventId();

  /**
   * Get the user the attendee is for.
   *
   * @return \Drupal\user\UserInterface
   *   The attendee user entity.
   */
  public function getAttendee();

  /**
   * Set the user the attendance is for.
   *
   * @param \Drupal\user\UserInterface $user
   *   The User entity.
   *
   * @return \Drupal\intercept_event\Entity\EventAttendanceInterface
   *   The called Event Attendance entity.
   */
  public function setAttendee(UserInterface $user);

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
   * Sets the published status of an Event Attendance.
   *
   * @param bool $published
   *   TRUE to set this entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\intercept_event\Entity\EventAttendanceInterface
   *   The called Event Attendance entity.
   */
  public function setPublished($published);

}
