<?php

namespace Drupal\intercept_core\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\intercept_core\ManagementManagerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * @var ManagementManagerInterface
   */
  protected $managementManager;

  use StringTranslationTrait;

  public function __construct(ManagementManagerInterface $management_manager) {
    $this->managementManager = $management_manager;
  }

  protected function createRoute($name) {
    return new Route(strtr($name, '_', '-'));
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->managementManager->getPages() as $id => $page) {
      $page = (object) $page;
      $permissions = [
        "access all management pages",
      ];
      // Add a basic permission for the default page and the menu item.
      $permissions[] = ($page->key == 'default') ? "access management" : "access management page {$page->key}";
      // Build a route that is indpendent of a user id context. 
      $collection->add("$id.redirect", $this->createRoute("/account/manage/{$page->key}")
        ->addDefaults([
          '_controller' => '\Drupal\intercept_core\Controller\UserAccount::userRedirect',
          'route_name' => $id,
        ])
        ->setOption('_admin_route', !empty($page->admin_route))
        ->addRequirements([
          '_permission' => implode('+', $permissions),
        ])
      );

      // Build the route that is rerouted to by the previous route.
      $collection->add($id, $this->createRoute("/user/{user}/manage/{$page->key}")
        ->addDefaults([
          '_controller' => $page->controller,
          '_title' => $page->title,
        ])
        ->setOption('_admin_route', !empty($page->admin_route))
        ->setOption('_page_name', "{$page->key}")
        ->addRequirements([
          '_permission' => implode('+', $permissions),
        ]));
    }
  }
}
