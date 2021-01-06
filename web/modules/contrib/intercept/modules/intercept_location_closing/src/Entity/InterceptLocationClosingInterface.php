<?php

namespace Drupal\intercept_location_closing\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Location Closing entities.
 *
 * @ingroup intercept_location_closing
 */
interface InterceptLocationClosingInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Location Closing name.
   *
   * @return string
   *   Name of the Location Closing.
   */
  public function getName();

  /**
   * Sets the Location Closing name.
   *
   * @param string $name
   *   The Location Closing name.
   *
   * @return \Drupal\intercept_location_closing\Entity\InterceptLocationClosingInterface
   *   The called Location Closing entity.
   */
  public function setName($name);

  /**
   * Gets the Location closing start time.
   *
   * @return string
   *   The closing start time value.
   */
  public function getStartTime();

  /**
   * Gets the Location closing start time.
   *
   * @return string
   *   The closing start time value.
   */
  public function getEndTime();

  /**
   * Gets the Location closing Location entities.
   *
   * @return \Drupal\Node\NodeInterface[]
   *   The Location Nodes.
   */
  public function getLocations();

  /**
   * Gets the Location closing visitor message.
   *
   * @return string
   *   The closing message.
   */
  public function getMessage();

  /**
   * Gets the Location Closing creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Location Closing.
   */
  public function getCreatedTime();

  /**
   * Sets the Location Closing creation timestamp.
   *
   * @param int $timestamp
   *   The Location Closing creation timestamp.
   *
   * @return \Drupal\intercept_location_closing\Entity\InterceptLocationClosingInterface
   *   The called Location Closing entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Location Closing published status indicator.
   *
   * Unpublished Location Closing are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Location Closing is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Location Closing.
   *
   * @param bool $published
   *   TRUE to set this Location Closing to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\intercept_location_closing\Entity\InterceptLocationClosingInterface
   *   The called Location Closing entity.
   */
  public function setPublished($published);

}
