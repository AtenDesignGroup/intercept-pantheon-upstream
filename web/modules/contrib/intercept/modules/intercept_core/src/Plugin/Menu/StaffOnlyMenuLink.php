<?php

namespace Drupal\intercept_core\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;

class StaffOnlyMenuLink extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    $roles = \Drupal::currentUser()->getRoles();
    if (in_array('intercept_registered_customer', $roles)) {
      return FALSE;
    }
    return TRUE;
  }

}
