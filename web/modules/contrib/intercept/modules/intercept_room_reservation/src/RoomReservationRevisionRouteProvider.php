<?php

namespace Drupal\intercept_room_reservation;

use Drupal\entity\Routing\RevisionRouteProvider;

class RoomReservationRevisionRouteProvider extends RevisionRouteProvider {
    protected function getRevisionHistoryRoute($entity_type) {
      return parent::getRevisionHistoryRoute($entity_type)->setOption('_admin_route', TRUE);
    }

}