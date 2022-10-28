<?php

namespace Drupal\intercept_bulk_room_reservation\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;

/**
 * Specifies permissions for viewing this module's related menu items.
 */
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
        // Don't show these menu items to customers. Staff only.
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    return FALSE;
  }

}
