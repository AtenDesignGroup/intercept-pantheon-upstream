<?php

namespace Drupal\intercept_room_reservation\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route = $collection->get('entity.room_reservation.canonical');
    $route->setDefault('_title', 'Reservation Details');
    $route->setDefault('_title_callback', NULL);

    $route = $collection->get('entity.room_reservation.edit_form');
    $route->setDefault('_title', 'Edit Reservation');
    $route->setDefault('_title_callback', NULL);
  }

}
