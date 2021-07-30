<?php

namespace Drupal\intercept_certification;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
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
class CertificationStorage extends SqlContentEntityStorage implements CertificationStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(CertificationInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {certification_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {certification_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(CertificationInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {certification_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('certification_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
