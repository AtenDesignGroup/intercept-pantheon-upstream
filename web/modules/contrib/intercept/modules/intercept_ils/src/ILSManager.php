<?php

namespace Drupal\intercept_ils;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * ILS plugin manager.
 *
 * This defines a new plugin type based on Joe Shindelar's DC 2015 example.
 * See: https://youtu.be/gd6s4wC_bP4?t=2979
 * See: https://drupalize.me/blog/201409/unravelling-drupal-8-plugin-system.
 */
class ILSManager extends DefaultPluginManager {

  /**
   * Constructs an ILSManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ILS', $namespaces, $module_handler, 'Drupal\intercept_ils\ILSInterface', 'Drupal\intercept_ils\Annotation\ILS');

    $this->alterInfo('intercept_ils_info');
    $this->setCacheBackend($cache_backend, 'intercept_ils');
  }

}
