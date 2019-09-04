<?php

namespace Drupal\intercept_core;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

class InterceptHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * Builds a new route to modify the status of an entity.
   *
   * @param $entity_type
   * @param $operation
   * @return Route
   */
  protected function getUpdateStatusFormRoutes($entity_type, $operation) {
    /** @var $entity_type EntityTypeInterface */
    if ($entity_type->hasLinkTemplate("{$operation}-form")) {
      $entity_type_id = $entity_type->id();
      $operation_label = ucwords($operation);
      $route = new Route($entity_type->getLinkTemplate("{$operation}-form"));
      // Use the edit form handler, if available, otherwise default.
      $route
        ->setDefaults([
          '_entity_form' => "{$entity_type_id}.{$operation}",
          '_title' => "{$operation_label} {$entity_type->getLabel()}",
        ])
        ->setRequirement('_entity_access', "{$entity_type_id}.{$operation}")
        ->setOption('parameters', [
            $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      // Entity types with serial IDs can specify this in their route
      // requirements, improving the matching process.
      if ($this->getEntityTypeIdKeyType($entity_type) === 'integer') {
        $route->setRequirement($entity_type_id, '\d+');
      }
      return $route;
    }
  }
}
