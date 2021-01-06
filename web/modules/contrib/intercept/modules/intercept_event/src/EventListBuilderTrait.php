<?php

namespace Drupal\intercept_event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Trait for functions to help build Event lists.
 */
trait EventListBuilderTrait {

  /**
   * The Event Node.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   *   The Event Node.
   */
  protected $event;

  /**
   * Sets the Event Node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Event Node.
   */
  public function setEvent(EntityInterface $entity) {
    $this->event = $entity;
  }

  /**
   * Gets the Event Node.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The Event Node.
   */
  public function getEvent() {
    return $this->event;
  }

  /**
   * Loads entity IDs using a pager sorted by the entity id.
   *
   * @return array
   *   An array of entity IDs.
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort('created', 'DESC');

    if (!empty($this->getEvent())) {
      $query->condition('field_event', $this->event->id(), '=');
      $this->limit = FALSE;
    }
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    foreach ($operations as &$operation) {
      $operation['url']->setOption('query', [
        'destination' => Url::fromRoute('<current>')->toString(),
      ]);
    }
    return $operations;
  }

  /**
   * Adds event header.
   *
   * @param array $header
   *   The event header.
   */
  protected function addEventHeader(array &$header) {
    if (empty($this->getEvent())) {
      $header['event'] = $this->t('Event');
    }
  }

  /**
   * Adds event header.
   *
   * @param array $row
   *   The event row.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  protected function addEventRow(array &$row, EntityInterface $entity) {
    if (empty($this->getEvent())) {
      $row['event'] = !empty($entity->field_event->entity) ? $entity->field_event->entity->link() : '';
    }
  }

  /**
   * Gets a link to the User.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $user_field
   *   (optional) The user field string.
   *
   * @return string
   *   An HTML string containing a link to the user.
   */
  protected function getUserLink(EntityInterface $entity, $user_field = 'field_user') {
    if (empty($entity->{$user_field}->entity)) {
      return '';
    }
    return $this->getLink($entity->{$user_field}->entity, $entity->{$user_field}->entity->getUsername());
  }

  /**
   * Gets a link to the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $title
   *   (optional) The default link title.
   *
   * @return string
   *   An HTML string containing a link to the entity.
   */
  protected function getLink(EntityInterface $entity = NULL, $title = NULL) {
    if (empty($entity)) {
      return '';
    }
    return $title ? $entity->toLink($title)->toString() : $entity->toLink()->toString();
  }

}
