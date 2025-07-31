<?php

namespace Drupal\intercept_event\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * Manages locking for event registrations.
 */
class EventRegistrationEventLock {

  /**
   * The lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * Constructs a new EventRegistrationEventLock object.
   *
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   */
  public function __construct(LockBackendInterface $lock) {
    $this->lock = $lock;
  }

  /**
   * Acquires a lock for an event registration.
   *
   * @param \Drupal\Core\Entity\EntityInterface $event
   *   The event entity.
   *
   * @return bool
   *   TRUE if the lock is acquired, FALSE otherwise.
   */
  public function acquireLock(EntityInterface $event) {
    return $this->lock->acquire($this->getLockId($event));
  }

  /**
   * Releases a lock for an event registration.
   *
   * @param \Drupal\Core\Entity\EntityInterface $event
   *   The event entity.
   */
  public function releaseLock(EntityInterface $event) {
    $this->lock->release($this->getLockId($event));
  }

  /**
   * Gets the lock ID for an event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $event
   *   The event entity.
   *
   * @return string
   *   The event specific lock ID.
   */
  private function getLockId(EntityInterface $event) {
    return "event_registration_{$event->id()}";
  }

}
