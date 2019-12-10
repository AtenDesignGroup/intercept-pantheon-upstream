<?php

namespace Drupal\intercept_core;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines an interface for manager_pages managers.
 */
interface ManagementManagerInterface extends PluginManagerInterface {

  /**
   * Gets management plugin page information.
   */
  public function getPages();

}
