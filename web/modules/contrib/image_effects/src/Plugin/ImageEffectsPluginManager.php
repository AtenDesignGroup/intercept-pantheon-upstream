<?php

namespace Drupal\image_effects\Plugin;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Symfony\Component\DependencyInjection\Container;

/**
 * Plugin manager for image_effects plugins.
 */
class ImageEffectsPluginManager extends DefaultPluginManager {

  /**
   * The configuration object.
   *
   * @var \Drupal\Core\Config\Config
   *
   * @deprecated in image_effects:8.x-3.5 and is removed from
   *   image_effects:4.0.0. Access the config factory directly instead.
   *
   * @see https://www.drupal.org/project/image_effects/issues/3399654
   */
  protected $config;

  /**
   * Constructs an ImageEffectsPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param string $type
   *   The plugin type, for example 'color_selector'.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    $type,
    protected readonly ConfigFactoryInterface $configFactory
  ) {
    $path = Container::camelize($type);
    // @phpstan-ignore-next-line
    $this->config = $this->configFactory->get('image_effects.settings');
    parent::__construct("Plugin/image_effects/{$path}", $namespaces, $module_handler);
    $this->alterInfo("image_effects_{$type}_plugin_info");
    $this->setCacheBackend($cache_backend, "image_effects_{$type}_plugins");
    $this->defaults += [
      'plugin_type' => $type,
    ];
  }

  /**
   * Get the 'image_effects' plugin type.
   *
   * @return string
   *   The plugin type.
   */
  public function getType() {
    return $this->defaults['plugin_type'];
  }

  /**
   * Returns an instance of the specified 'image_effects' plugin.
   *
   * @param string $plugin_id
   *   The plugin id.
   *
   * @return \Drupal\image_effects\Plugin\ImageEffectsPluginBaseInterface
   *   An instance of the specified 'image_effects' plugin.
   */
  public function getPlugin($plugin_id = NULL) {
    $plugin_id = $plugin_id ?: $this->configFactory->get('image_effects.settings')->get($this->getType() . '.plugin_id');
    $plugins = $this->getAvailablePlugins();

    // Check if plugin is available.
    if (!isset($plugins[$plugin_id]) || !class_exists($plugins[$plugin_id]['class'])) {
      trigger_error("image_effects " . $this->getType() . " handling plugin '$plugin_id' is no longer available.", E_USER_ERROR);
      $plugin_id = NULL;
    }

    return $this->createInstance($plugin_id, ['plugin_type' => $this->getType()]);
  }

  /**
   * Gets a list of available plugins.
   *
   * @return array
   *   An array with the plugin ids as keys and the definitions as values.
   */
  public function getAvailablePlugins() {
    $plugins = $this->getDefinitions();
    $output = [];
    foreach ($plugins as $id => $definition) {
      // Only allow plugins that are available.
      if (call_user_func($definition['class'] . '::isAvailable')) {
        $output[$id] = $definition;
      }
    }
    return $output;
  }

  /**
   * Gets a formatted list of available plugins.
   *
   * @return array
   *   An array with the plugin ids as keys and the descriptions as values.
   */
  public function getPluginOptions() {
    $options = [];
    foreach ($this->getAvailablePlugins() as $plugin) {
      $options[$plugin['id']] = new FormattableMarkup('<b>@title</b> - @description', [
        '@title' => $plugin['short_title'],
        '@description' => $plugin['help'],
      ]);
    }
    return $options;
  }

}
