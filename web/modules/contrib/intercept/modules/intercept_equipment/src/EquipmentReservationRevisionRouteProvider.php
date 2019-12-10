<?php

namespace Drupal\intercept_equipment;

use Drupal\entity\Routing\RevisionRouteProvider;

/**
 * Provides revision routes for Equipment Reservations.
 */
class EquipmentReservationRevisionRouteProvider extends RevisionRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getRevisionHistoryRoute($entity_type) {
    return parent::getRevisionHistoryRoute($entity_type)->setOption('_admin_route', TRUE);
  }

}
