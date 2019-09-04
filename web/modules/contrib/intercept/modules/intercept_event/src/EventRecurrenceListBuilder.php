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
    $row['base'] = $entity->event->entity ? $entity->event->entity->label() : '';
    $row['date'] = $entity->getDate();
    $handler = $entity->getRecurHandler();
    $row['rule'] = $handler ? $handler->humanReadable() : '';
    $row['events'] = count($entity->getEvents());
    return $row + parent::buildRow($entity);
  }

}
