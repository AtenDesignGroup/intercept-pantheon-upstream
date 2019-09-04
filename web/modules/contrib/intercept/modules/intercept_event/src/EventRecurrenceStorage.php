<?php

namespace Drupal\intercept_event;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
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
class EventRecurrenceStorage extends SqlContentEntityStorage implements EventRecurrenceStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(EventRecurrenceInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {event_recurrence_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {event_recurrence_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(EventRecurrenceInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {event_recurrence_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('event_recurrence_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
