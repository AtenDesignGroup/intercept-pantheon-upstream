<?php

namespace Drupal\intercept_event\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;

class ExportMenuLink extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    $current_user = \Drupal::currentUser();
    $permission = $current_user->hasPermission('analyze events');

    if ($permission) {
      return TRUE;
    }
    return FALSE;
  }

}
