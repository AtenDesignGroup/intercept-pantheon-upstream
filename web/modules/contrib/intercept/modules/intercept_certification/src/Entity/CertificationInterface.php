<?php

namespace Drupal\intercept_certification\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Certification entities.
 *
 * @ingroup intercept_certification
 */
interface CertificationInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Certification name.
   *
   * @return string
   *   Name of the Certification.
   */
  public function getName();

  /**
   * Sets the Certification name.
   *
   * @param string $name
   *   The Certification name.
   *
   * @return \Drupal\intercept_certification\Entity\CertificationInterface
   *   The called Certification entity.
   */
  public function setName($name);

  /**
   * Gets the Certification creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Certification.
   */
  public function getCreatedTime();

  /**
   * Sets the Certification creation timestamp.
   *
   * @param int $timestamp
   *   The Certification creation timestamp.
   *
   * @return \Drupal\intercept_certification\Entity\CertificationInterface
   *   The called Certification entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Certification revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Certification revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\intercept_certification\Entity\CertificationInterface
   *   The called Certification entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Certification revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Certification revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\intercept_certification\Entity\CertificationInterface
   *   The called Certification entity.
   */
  public function setRevisionUserId($uid);

}
