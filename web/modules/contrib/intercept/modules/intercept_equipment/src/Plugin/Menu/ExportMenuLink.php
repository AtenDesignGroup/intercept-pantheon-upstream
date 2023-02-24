<?php

namespace Drupal\intercept_equipment\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;

/**
 *
 */
class ExportMenuLink extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    $current_user = \Drupal::currentUser();
    $permission = $current_user->hasPermission('access equipment reservations export');

    if ($permission) {
      return TRUE;
    }
    return FALSE;
  }

}
