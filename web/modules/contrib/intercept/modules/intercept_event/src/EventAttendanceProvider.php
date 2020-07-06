<?php

namespace Drupal\intercept_event;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Default implementation of the event attendance provider.
 */
class EventAttendanceProvider implements EventAttendanceProviderInterface {

  /**
   * The event attendance storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $eventAttendanceStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new EventAttendanceProvider object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->eventAttendanceStorage = $entity_type_manager->getStorage('event_attendance');
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getEventAttendances(AccountInterface $account = NULL) {
    $event_attendances = [];
    $event_attendance_ids = $this->getEventAttendanceIds($account);
    if ($event_attendance_ids) {
      $event_attendances = $this->eventAttendanceStorage->loadMultiple($event_attendance_ids);
    }

    return $event_attendances;
  }

  /**
   * {@inheritdoc}
   */
  public function getEventAttendanceIds(AccountInterface $account = NULL) {
    $account = $account ?: $this->currentUser;
    if ($account->isAnonymous()) {
      return [];
    }
    $query = $this->eventAttendanceStorage->getQuery()
      ->condition('status', '1')
      ->condition('field_user', $account->id())
      ->accessCheck(FALSE);
    return $query->execute();
  }

}
