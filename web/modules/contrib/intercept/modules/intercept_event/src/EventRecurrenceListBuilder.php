<?php

namespace Drupal\intercept_event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Event Recurrence entities.
 *
 * @ingroup intercept_event
 */
class EventRecurrenceListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort('changed', 'DESC');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['base'] = $this->t('Recurrence of');
    $header['date'] = $this->t('Date');
    $header['rule'] = $this->t('Rule');
    $header['events'] = $this->t('Events');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\intercept_event\Entity\EventRecurrence */
    $row['id'] = Link::createFromRoute(
      $entity->id(),
      'entity.event_recurrence.edit_form',
      ['event_recurrence' => $entity->id()]
    );
    $row['base'] = $entity->event->entity ? $entity->event->entity->toLink() : '';
    $row['date'] = $entity->getDate();
    $row['rule'] = $entity->getRecurReadable();
    $row['events'] = count($entity->getEvents());
    return $row + parent::buildRow($entity);
  }

}
