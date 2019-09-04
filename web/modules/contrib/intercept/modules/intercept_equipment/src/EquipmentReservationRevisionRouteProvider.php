<?php

namespace Drupal\intercept_equipment;

use Drupal\entity\Routing\RevisionRouteProvider;

class EquipmentReservationRevisionRouteProvider extends RevisionRouteProvider {
    protected function getRevisionHistoryRoute($entity_type) {
      return parent::getRevisionHistoryRoute($entity_type)->setOption('_admin_route', TRUE);
    }

}