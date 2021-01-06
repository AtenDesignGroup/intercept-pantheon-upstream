<?php

namespace Drupal\intercept_core\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\intercept_core\ManagementManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Derives menu links from Intercept management manager pages.
 */
class InterceptMenuLinks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The Intercept management manager.
   *
   * @var \Drupal\intercept_core\ManagementManagerInterface
   *   The Intercept management manager.
   */
  protected $managementManager;

  /**
   * The route provider to load routes by name.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Constructs an InterceptMenuLinks object.
   *
   * @param \Drupal\intercept_core\ManagementManagerInterface $management_manager
   *   The Intercept management manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider to load routes by name.
   */
  public function __construct(ManagementManagerInterface $management_manager, RouteProviderInterface $route_provider) {
    $this->managementManager = $management_manager;
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.intercept_management'),
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach ($this->managementManager->getPages() as $id => $page) {
      $page = (object) $page;
      // Skip if no menu link is specified.
      if (isset($page->menu_link) && !$page->menu_link) {
        continue;
      }
      if (isset($page->route_name) && !$this->ensureRoute($page->route_name)) {
        continue;
      }

      $this->derivatives[$id] = [
        'title' => $page->title,
        'weight' => isset($page->menu_weight) ? $page->menu_weight : 0,
        'route_name' => $this->getPageRouteName($page),
        'menu_name' => 'intercept-manage',
        'parent' => isset($page->parent) ? $page->parent : NULL,
      ];
    }

    return $this->derivatives;
  }

  /**
   * Checks if route was specified, is a redirect, or a regular user path.
   *
   * @param $page
   *   A single item from ManagementManager::getPages() converted to an object.
   *
   * @return string
   *   The route_name string.
   */
  private function getPageRouteName($page) {
    if (!empty($page->route_name)) {
      return $page->route_name;
    }
    return $page->user_context_redirect ? "{$page->id}.redirect" : "{$page->id}";
  }

  /**
   * Verifies that a route exists.
   *
   * @param string $route_name
   *   The route name.
   *
   * @return bool
   *   Whether the route exists.
   */
  protected function ensureRoute($route_name) {
    try {
      return $this->routeProvider->getRouteByName($route_name);
    }
    catch (RouteNotFoundException $e) {
      return FALSE;
    }
  }

}
