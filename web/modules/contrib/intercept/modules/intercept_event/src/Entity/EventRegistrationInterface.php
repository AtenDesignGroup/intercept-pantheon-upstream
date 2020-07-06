<?php

namespace Drupal\intercept_event\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Event Registration entities.
 *
 * @ingroup intercept_event
 */
interface EventRegistrationInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Event Registration title based on the name and date.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The Event Registration title.
   */
  public function getTitle();

  /**
   * Gets the total event registrants.
   *
   * @return int
   *   The total number of registrants.
   */
  public function total();

  /**
   * Get the user the registration is for.
   *
   * @return \Drupal\user\UserInterface
   *   The registration user entity.
   */
  public function getRegistrant();

  /**
   * Gets the original event registration status.
   *
   * @return string|bool
   *   The original event registration status, or FALSE.
   */
  public function getOriginalStatus();

  /**
   * Gets the current event registration status.
   *
   * @return string
   *   The current event registration status, or FALSE.
   */
  public function getStatus();

  /**
   * Whether the event registration status is changing on save.
   *
   * @return bool
   *   Whether the event registration status is changing on save.
   */
  public function statusHasChanged();

  /**
   * Gets the Event Registration creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Event Registration.
   */
  public function getCreatedTime();

  /**
   * Sets the Event Registration creation timestamp.
   *
   * @param int $timestamp
   *   The Event Registration creation timestamp.
   *
   * @return \Drupal\intercept_event\Entity\EventRegistrationInterface
   *   The called Event Registration entity.
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

}
