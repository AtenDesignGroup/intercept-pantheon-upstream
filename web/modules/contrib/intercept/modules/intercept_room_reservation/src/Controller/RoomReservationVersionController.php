<?php

namespace Drupal\intercept_room_reservation\Controller;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\entity\Controller\RevisionOverviewController;

/**
 * Returns responses for Room reservation version history routes.
 */
class RoomReservationVersionController extends RevisionOverviewController {

  /**
   * Generates an overview table of older revisions of a room reservation.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array
   *   A render array.
   */
  public function revisionOverviewController(RouteMatchInterface $route_match) {
    $build = parent::revisionOverviewController($route_match);
    $url = Url::fromRoute('view.intercept_room_reservations.page');
    $options = [
      'attributes' => [
        'class' => [
          'more-link',
          'more-link--back',
        ],
      ],
    ];
    $url->setOptions($options);
    $link = Link::fromTextAndUrl(t('Return to room reservations list'), $url);

    $link = $link->toRenderable();
    $build['link'] = $link;
    return $build;
  }

}
