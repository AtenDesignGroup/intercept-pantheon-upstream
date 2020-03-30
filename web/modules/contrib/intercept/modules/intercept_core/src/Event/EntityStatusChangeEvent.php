<?php

namespace Drupal\intercept_core\Event;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the entity status change event.
 */
class EntityStatusChangeEvent extends Event {

  const CHANGE = 'intercept_entity_status_change';

  /**
   * The entity that has been changed.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The previous entity status.
   *
   * @var string
   */
  protected $previousStatus;

  /**
   * The new entity status.
   *
   * @var string
   */
  protected $newStatus;

  /**
   * Constructs a new EntityStatusChangeEvent.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $previous_status
   *   The previous entity status.
   * @param string $new_status
   *   The new entity status.
   */
  public function __construct(EntityInterface $entity, $previous_status, $new_status) {
    $this->entity = $entity;
    $this->previousStatus = $previous_status;
    $this->newStatus = $new_status;
  }

  /**
   * Gets the entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Gets the previous entity status.
   *
   * @return string
   *   The previous entity status.
   */
  public function getPreviousStatus() {
    return $this->previousStatus;
  }

  /**
   * Gets the previous entity status.
   *
   * @return string
   *   The previous entity status.
   */
  public function getNewStatus() {
    return $this->newStatus;
  }

}
