<?php

namespace Drupal\intercept_event;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Default implementation of the event registration provider.
 */
class EventRegistrationProvider implements EventRegistrationProviderInterface {

  /**
   * The event registration storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $eventRegistrationStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new EventRegistrationProvider object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->eventRegistrationStorage = $entity_type_manager->getStorage('event_registration');
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getEventRegistrations(AccountInterface $account = NULL) {
    $event_registrations = [];
    $event_registration_ids = $this->getEventRegistrationIds($account);
    if ($event_registration_ids) {
      $event_registrations = $this->eventRegistrationStorage->loadMultiple($event_registration_ids);
    }

    return $event_registrations;
  }

  /**
   * {@inheritdoc}
   */
  public function getEventRegistrationIds(AccountInterface $account = NULL) {
    $account = $account ?: $this->currentUser;
    if ($account->isAnonymous()) {
      return [];
    }
    $query = $this->eventRegistrationStorage->getQuery()
      ->condition('field_user', $account->id())
      ->accessCheck(FALSE);
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getEventRegistrationsByEvent(NodeInterface $event) {
    $event_registrations = [];
    $event_registration_ids = $this->getEventRegistrationIdsByEvent($event);
    if ($event_registration_ids) {
      $event_registrations = $this->eventRegistrationStorage->loadMultiple($event_registration_ids);
    }

    return $event_registrations;
  }

  /**
   * {@inheritdoc}
   */
  public function getEventRegistrationIdsByEvent(NodeInterface $event) {
    $query = $this->eventRegistrationStorage->getQuery()
      ->condition('field_event', $event->id())
      ->accessCheck(FALSE);
    return $query->execute();
  }

}
