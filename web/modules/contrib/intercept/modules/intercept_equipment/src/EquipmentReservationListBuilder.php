<?php

namespace Drupal\intercept_equipment;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Equipment reservation entities.
 *
 * @ingroup intercept_equipment_reservation
 */
class EquipmentReservationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Reservation');
    $header['equipment'] = $this->t('Equipment');
    $header['location'] = $this->t('Location');
    $header['user'] = $this->t('User');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // Fix the dates to be non-UTC in the display.
    // Dates/times of reservation.
    $reservation_dates = $entity->get('field_dates')->getValue();
    $dateTime = new DrupalDateTime($reservation_dates[0]['value'], 'UTC');
    $reservation_start_date = date('m-d-Y g:i A', $dateTime->getTimestamp());
    $dateTime = new DrupalDateTime($reservation_dates[0]['end_value'], 'UTC');
    $reservation_end_date = date('m-d-Y g:i A', $dateTime->getTimestamp());
    /* @var $entity \Drupal\intercept_equipment\Entity\EquipmentReservation */
    $row['name'] = $entity->toLink($reservation_start_date . ' - ' . $reservation_end_date)->toString();
    $row['equipment'] = $this->getEntityLabel($entity->field_equipment->entity, $this->t('No equipment'));
    $row['location'] = $entity->get('field_location')->entity ? $entity->get('field_location')->entity->label() : '';
    $row['user'] = $this->getEntityLabel($entity->field_user->entity, $this->t('No user'));
    return $row + parent::buildRow($entity);
  }

  /**
   * Gets an HTML link string to an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being listed.
   * @param string $default
   *   The fallback label string.
   *
   * @return string
   *   The HTML link string to an entity.
   */
  private function getEntityLabel(EntityInterface $entity = NULL, $default = '') {
    return $entity ? $entity->toLink()->toString() : $default;
  }

}
