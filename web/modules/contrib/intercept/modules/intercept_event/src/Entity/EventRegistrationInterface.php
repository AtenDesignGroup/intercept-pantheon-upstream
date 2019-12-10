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

}
