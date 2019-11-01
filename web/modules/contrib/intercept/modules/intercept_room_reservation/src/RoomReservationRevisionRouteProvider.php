<?php

namespace Drupal\intercept_room_reservation;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\Routing\RevisionRouteProvider;

class RoomReservationRevisionRouteProvider extends RevisionRouteProvider {
    protected function getRevisionHistoryRoute(EntityTypeInterface $entity_type) {
      return parent::getRevisionHistoryRoute($entity_type)->setOption('_admin_route', TRUE);
    }
}
