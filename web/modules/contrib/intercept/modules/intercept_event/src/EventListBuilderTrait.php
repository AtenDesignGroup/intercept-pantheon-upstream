<?php

namespace Drupal\intercept_event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

trait EventListBuilderTrait {

  protected $event;

  public function setEvent(EntityInterface $entity) {
    $this->event = $entity;
  }

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
      ->sort($this->entityType->getKey('id'));

    if (!empty($this->getEvent())) {
      $query->condition('field_event', $this->event->id(), '=');
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

  protected function addEventHeader(&$header) {
    if (empty($this->getEvent())) {
      $header['event'] = $this->t('Event');
    }
  }

  protected function addEventRow(&$row, $entity) {
    if (empty($this->getEvent())) {
      $row['event'] = !empty($entity->field_event->entity) ? $entity->field_event->entity->link() : '';
    }
  }

  protected function getUserLink(EntityInterface $entity, $user_field = 'field_user') {
    if (empty($entity->{$user_field}->entity)) {
      return '';
    }
    return $this->getLink($entity->{$user_field}->entity, $entity->{$user_field}->entity->getUsername());
  }

  protected function getLink(EntityInterface $entity = NULL, $title = NULL) {
    if (empty($entity)) {
      return '';
    }
    return $title ? $entity->link($title) : $entity->link();
  }

}
