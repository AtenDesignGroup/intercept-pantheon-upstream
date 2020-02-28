<?php

namespace Drupal\intercept_core\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;

class ConfigMenuLink extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    $current_user = \Drupal::currentUser();
    $permission = $current_user->hasPermission('access management page system_configuration');

    if ($permission) {
      return TRUE;
    }
    return FALSE;
  }

}
