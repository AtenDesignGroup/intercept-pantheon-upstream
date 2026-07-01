<?php

namespace Drupal\jsonapi_resources_test\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber for aliasing several Core user relationship routes.
 *
 * This is necessary to avoid errors when JSON:API attempts to create "self"
 * links for roles relationships.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The JSON:API base path.
   *
   * @var string
   */
  protected string $jsonApiBasePath;

  /**
   * Constructs a new instance.
   *
   * @param string $jsonapi_base_path
   *   The JSON:API base path.
   */
  public function __construct(string $jsonapi_base_path) {
    $this->jsonApiBasePath = $jsonapi_base_path;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route_names = [
      'jsonapi.current_user--current_user.roles.related',
      'jsonapi.current_user--current_user.roles.relationship.get',
    ];

    // Replace '/%jsonapi%' in the two routes we define for the 'roles'
    // relationship field. This doesn't happen automatically because these
    // routes are not custom JSON:API Resources routes -- they are aliases of
    // normal user roles routes defined by Core.
    foreach ($route_names as $route_name) {
      $route = $collection->get($route_name);

      if ($route !== NULL) {
        $old_path = $route->getPath();
        $new_path = str_replace('/%jsonapi%', $this->jsonApiBasePath, $old_path);

        $route->setPath($new_path);
      }
    }
  }

}
