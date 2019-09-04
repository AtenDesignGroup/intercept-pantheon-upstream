<?php

namespace Drupal\intercept_room_reservation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Class ReserveRoomController.
 */
class ReserveRoomController extends ControllerBase {

  /**
   * Reserve Room.
   *
   * @return array
   *   Return Room reservation page.
   */
  public function reserveRoom() {
    if ($this->currentUser()->isAnonymous()) {
      return $this->redirect('user.login', [
        'destination' => Url::fromRoute('<current>')->toString(),
      ]);
    }
    $build = [];
    $build['#attached']['library'][] = 'intercept_room_reservation/reserveRoom';
    $build['#markup'] = '';
    $build['intercept_room_reserve']['#markup'] = '<div id="reserveRoomRoot"></div>';

    return $build;
  }

}
