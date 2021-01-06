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
   * The Intercept management plugin manager.
   *
   * @var \Drupal\intercept_core\ManagementManagerInterface
   */
  protected $managementManager;

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(ManagementManagerInterface $management_manager) {
    $this->managementManager = $management_manager;
  }

  /**
   * Creates a route given a local URL.
   *
   * @param string $name
   *   The local URL string.
   *
   * @return \Symfony\Component\Routing\Route
   *   A Route object.
   */
  protected function createRoute($name) {
    return new Route(strtr($name, '_', '-'));
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->managementManager->getPages() as $id => $page) {
      $page = (object) $page;

      if (!empty($page->route_name)) {
        continue;
      }

      $permissions = [
        "access all management pages",
      ];

      // Add a basic permission for the default page and the menu item.
      $permissions[] = ($page->key == 'default') ? "access management" : "access management page {$page->key}";

      $path = $page->path ?? "/manage/{$page->key}";

      // By default we create a default path that redirects to a user/{user} path.
      if ($page->user_context_redirect) {
        // Build a route that is independent of a user id context.
        $collection->add("$id.redirect", $this->createRoute($path)
          ->addDefaults([
            '_controller' => '\Drupal\intercept_core\Controller\UserAccount::userRedirect',
            'route_name' => $id,
          ])
          ->setOption('_admin_route', !empty($page->admin_route))
          ->addRequirements([
            '_permission' => implode('+', $permissions),
          ])
        );

        // Set the path for the user context route.
        $path = "/user/{user}/manage/{$page->key}";
      }

      // Build the route that is rerouted to by the previous route.
      $collection->add($id, $this->createRoute($path)
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

    // Alter the core user profile (Account Summary) page
    // to only display for customers.
    if ($route = $collection->get('user.page')) {
      $route->setRequirement('_role', 'intercept_registered_customer');
    }
  }

}
