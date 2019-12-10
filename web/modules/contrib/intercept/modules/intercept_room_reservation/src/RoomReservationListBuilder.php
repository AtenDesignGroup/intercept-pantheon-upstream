<?php

namespace Drupal\intercept_room_reservation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\intercept_core\SettableListBuilderTrait;

/**
 * Defines a class to build a listing of Room reservation entities.
 *
 * @ingroup intercept_room_reservation
 */
class RoomReservationListBuilder extends EntityListBuilder {

  use SettableListBuilderTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Reservation');
    $header['room'] = $this->t('Room');
    $header['location'] = $this->t('Location');
    $header['user'] = $this->t('User');
    $header['status'] = $this->t('Status');
    $header = array_merge($header, parent::buildHeader());
    return $this->hideHeaderColumns($header);
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\intercept_room_reservation\Entity\RoomReservation */
    $row['name'] = $entity->link($entity->getDateRange('UTC'));
    $row['room'] = $this->getEntityLabel($entity->field_room->entity, $this->t('No room'));
    $row['location'] = $entity->getLocation() ? $entity->getLocation()->link() : '';
    $row['user'] = $this->getEntityLabel($entity->field_user->entity, $this->t('No user'));
    $row['status'] = $entity->field_status->getString();
    $row = array_merge($row, parent::buildRow($entity));
    return $row;
  }

  /**
   * Loads entity IDs using a pager sorted by the entity id.
   *
   * @return array
   *   An array of entity IDs.
   */
  protected function getEntityIds() {
    // If using SettableListBuilderTrait::setEntityIds then use that.
    if (isset($this->entityIds)) {
      return $this->entityIds;
    }
    // Otherwise override EntityListBuilder::getEntityIds to change sort.
    $query = $this->getStorage()->getQuery()
      ->sort('created', 'DESC');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * Gets the Room Reservation label.
   *
   * @return string
   *   The Room Reservation label.
   */
  private function getEntityLabel(EntityInterface $entity = NULL, $default = '') {
    return $entity ? $entity->link() : $default;
  }

}
