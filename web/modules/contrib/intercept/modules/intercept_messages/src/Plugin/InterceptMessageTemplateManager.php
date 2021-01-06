<?php

namespace Drupal\intercept_messages\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Intercept message template plugin manager.
 */
class InterceptMessageTemplateManager extends DefaultPluginManager {

  /**
   * Constructs a new InterceptMessageTemplateManager object.
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
    parent::__construct('Plugin/InterceptMessageTemplate', $namespaces, $module_handler, 'Drupal\intercept_messages\InterceptMessageTemplateInterface', 'Drupal\intercept_messages\Annotation\InterceptMessageTemplate');

    $this->alterInfo('intercept_message_template_info');
    $this->setCacheBackend($cache_backend, 'intercept_message_template_plugins');
  }

  /**
   * Gets the plugin definitions for this type.
   *
   * @param string $type
   *   The type name.
   *
   * @return array
   *   An array of plugin definitions for this type.
   */
  public function getDefinitionsByType($type) {
    return array_filter($this->getDefinitions(), function ($definition) use ($type) {
      return $definition['type'] === $type;
    });
  }

  /**
   * Gets the plugin definitions for this type.
   *
   * @param array $types
   *   An array of type names.
   *
   * @return array
   *   An array of plugin definitions for this type.
   */
  public function getDefinitionsByTypes(array $types) {
    return array_filter($this->getDefinitions(), function ($definition) use ($types) {
      return in_array($definition['type'], $types);
    });
  }

}
