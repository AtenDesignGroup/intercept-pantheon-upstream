<?php

namespace Drupal\intercept_core;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;

/**
 * Provides the default intercept_management manager.
 */
class ManagementManager extends DefaultPluginManager implements ManagementManagerInterface {

  /**
   * Provides default values for all intercept_management plugins.
   *
   * @var array
   */
  protected $defaults = [
    // Add required and optional plugin properties.
    'id' => '',
    'label' => '',
  ];

  /**
   * Constructs a new ManagementManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    $this->moduleHandler = $module_handler;
    // Add more services as required.
    $this->setCacheBackend($cache_backend, 'intercept_management', ['intercept_management']);
    $this->alterInfo('intercept_management_info');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('intercept.management', $this->moduleHandler->getModuleDirectories());
      $this->discovery->addTranslatableProperty('label', 'label_context');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    // You can add validation of the plugin definition here.
    if (empty($definition['id'])) {
      throw new PluginException(sprintf('Example plugin property (%s) definition "is" is required.', $plugin_id));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPages() {
    $return = [];
    foreach ($this->getDefinitions() as $definition) {
      $definition = (object) $definition;
      if (empty($definition->pages)) {
        continue;
      }
      foreach ($definition->pages as $key => $page) {
        // Set a default value for the user_context_redirect setting.
        if (!isset($page['user_context_redirect'])) {
          $page['user_context_redirect'] = TRUE;
        }
        $return["{$definition->id}.management.{$key}"] = $page + [
          'id' => "{$definition->id}.management.{$key}",
          'title' => $page['title'],
          'key' => $key,
          'controller' => $definition->controller,
        ];
      }
    }
    return $return;
  }

}
