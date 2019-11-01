<?php

namespace Drupal\intercept_equipment;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\Routing\RevisionRouteProvider;

class EquipmentReservationRevisionRouteProvider extends RevisionRouteProvider {
    protected function getRevisionHistoryRoute(EntityTypeInterface $entity_type) {
      return parent::getRevisionHistoryRoute($entity_type)->setOption('_admin_route', TRUE);
    }

}
