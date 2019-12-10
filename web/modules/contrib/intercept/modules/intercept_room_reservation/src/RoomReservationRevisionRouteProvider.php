<?php

namespace Drupal\intercept_room_reservation;

use Drupal\entity\Routing\RevisionRouteProvider;

/**
 * Route providers for room reservation revisions.
 */
class RoomReservationRevisionRouteProvider extends RevisionRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getRevisionHistoryRoute($entity_type) {
    return parent::getRevisionHistoryRoute($entity_type)->setOption('_admin_route', TRUE);
  }

}
