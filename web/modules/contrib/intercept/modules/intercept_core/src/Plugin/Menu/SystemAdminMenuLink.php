<?php

namespace Drupal\intercept_core\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;

/**
 *
 */
class SystemAdminMenuLink extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    $roles = \Drupal::currentUser()->getRoles();
    if (!in_array('intercept_system_admin', $roles)) {
      return FALSE;
    }
    return TRUE;
  }

}
