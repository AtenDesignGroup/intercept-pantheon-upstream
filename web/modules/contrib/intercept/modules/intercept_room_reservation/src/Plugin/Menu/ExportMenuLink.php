<?php

namespace Drupal\intercept_room_reservation\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;

class ExportMenuLink extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    $current_user = \Drupal::currentUser();
    $permission = $current_user->hasPermission('access management page room_reservations_export');

    if ($permission) {
      return TRUE;
    }
    return FALSE;
  }

}
