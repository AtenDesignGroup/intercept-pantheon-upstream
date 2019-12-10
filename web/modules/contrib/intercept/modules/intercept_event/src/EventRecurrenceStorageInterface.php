<?php

namespace Drupal\intercept_event;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\intercept_event\Entity\EventRecurrenceInterface;

/**
 * Defines the storage handler class for Event Recurrence entities.
 *
 * This extends the base storage class, adding required special handling for
 * Event Recurrence entities.
 *
 * @ingroup intercept_event
 */
interface EventRecurrenceStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Event Recurrence revision IDs for a Event Recurrence.
   *
   * @param \Drupal\intercept_event\Entity\EventRecurrenceInterface $entity
   *   The Event Recurrence entity.
   *
   * @return int[]
   *   Event Recurrence revision IDs (in ascending order).
   */
  public function revisionIds(EventRecurrenceInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Event Recurrence author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Event Recurrence revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\intercept_event\Entity\EventRecurrenceInterface $entity
   *   The Event Recurrence entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(EventRecurrenceInterface $entity);

  /**
   * Unsets the language for all Event Recurrence with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
