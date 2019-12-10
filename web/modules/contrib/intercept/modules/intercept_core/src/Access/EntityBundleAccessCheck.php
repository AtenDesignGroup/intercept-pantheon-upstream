<?php

namespace Drupal\intercept_core\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Access check for entity bundles.
 */
class EntityBundleAccessCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $requirement = $route->getRequirement('_entity_access');
    $bundle = $route->getRequirement('_entity_bundle');
    list($entity_type, $operation) = explode('.', $requirement);
    $parameters = $route_match->getParameters();
    if ($parameters->has($entity_type)) {
      $entity = $parameters->get($entity_type);
      if ($entity->bundle() == $bundle) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::forbidden();
  }

}
