<?php

namespace Drupal\intercept_event\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

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
   * Sets the Event entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $event
   *   The event entity.
   *
   * @return $this
   */
  public function setEventEntity(EntityInterface $event);

  /**
   * Gets the parent entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The event entity.
   */
  public function getParentEntity();

  /**
   * Gets the parent ID.
   *
   * @return int
   *   The event entity ID.
   */
  public function getParentId();

  /**
   * Sets the parent entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $parent
   *   The parent entity.
   *
   * @return $this
   */
  public function setParentEntity(EntityInterface $parent);

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
   * Set the user the registration is for.
   *
   * @param \Drupal\user\UserInterface $user
   *   The User entity.
   *
   * @return \Drupal\intercept_event\Entity\EventRegistrationInterface
   *   The called Event Registration entity.
   */
  public function setRegistrant(UserInterface $user);

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
   * Gets validation constraint violations.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   A list of constraint violations. If the list is empty, validation
   *   succeeded.
   */
  public function validationWarnings();

}
