<?php

namespace Drupal\intercept_certification;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\intercept_certification\Entity\CertificationInterface;

/**
 * Defines the storage handler class for Certification entities.
 *
 * This extends the base storage class, adding required special handling for
 * Certification entities.
 *
 * @ingroup intercept_certification
 */
interface CertificationStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Certification revision IDs for a specific Certification.
   *
   * @param \Drupal\intercept_certification\Entity\CertificationInterface $entity
   *   The Certification entity.
   *
   * @return int[]
   *   Certification revision IDs (in ascending order).
   */
  public function revisionIds(CertificationInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Certification author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Certification revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\intercept_certification\Entity\CertificationInterface $entity
   *   The Certification entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(CertificationInterface $entity);

  /**
   * Unsets the language for all Certification with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
