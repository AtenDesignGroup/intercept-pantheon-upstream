<?php

namespace Drupal\intercept_event\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;

class ExportMenuLink extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    $current_user = \Drupal::currentUser();
    $permission = $current_user->hasPermission('access management page event_attendance_export');

    if ($permission) {
      return TRUE;
    }
    return FALSE;
  }

}
