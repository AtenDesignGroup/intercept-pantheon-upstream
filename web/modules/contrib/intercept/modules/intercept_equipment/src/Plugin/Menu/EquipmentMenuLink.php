<?php

namespace Drupal\intercept_equipment\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;

class EquipmentMenuLink extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    $current_user = \Drupal::currentUser();
    $permission = $current_user->hasPermission('add equipment reservation entities');

    if ($permission) {
      return TRUE;
    }
    return FALSE;
  }

}
