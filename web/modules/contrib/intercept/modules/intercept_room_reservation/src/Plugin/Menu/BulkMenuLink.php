<?php

namespace Drupal\intercept_room_reservation\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;

class BulkMenuLink extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    $current_user = \Drupal::currentUser();
    $permission = $current_user->hasPermission('administer bulk room reservation');

    if ($permission) {
      $roles = $current_user->getRoles();
      if (!in_array('intercept_registered_customer', $roles)) {
        return TRUE; // Don't show these menu items to customers. Staff only.
      }
      else {
        return FALSE;
      }
    }
    return FALSE;
  }

}
