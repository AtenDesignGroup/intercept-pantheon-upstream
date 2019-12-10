<?php

namespace Drupal\intercept_event;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\intercept_core\InterceptHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Event Registration entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class EventRegistrationHtmlRouteProvider extends InterceptHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if ($settings_form_route = $this->getSettingsFormRoute($entity_type)) {
      $collection->add("$entity_type_id.settings", $settings_form_route);
    }

    if ($cancel_form_route = $this->getUpdateStatusFormRoutes($entity_type, 'cancel')) {
      $collection->add("entity.$entity_type_id.cancel_form", $cancel_form_route);
    }

    // TODO: Use this for other event related entities.
    if ($event_form_route = $this->getEventFormRoutes($entity_type)) {
      $collection->add("entity.$entity_type_id.event_form", $event_form_route);
    }

    return $collection;
  }

  /**
   * Gets the settings form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getSettingsFormRoute(EntityTypeInterface $entity_type) {
    if (!$entity_type->getBundleEntityType()) {
      $route = new Route("/admin/structure/{$entity_type->id()}/settings");
      $route
        ->setDefaults([
          '_form' => 'Drupal\intercept_event\Form\EventRegistrationSettingsForm',
          '_title' => "{$entity_type->getLabel()} settings",
        ])
        ->setRequirement('_permission', $entity_type->getAdminPermission())
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

  /**
   * Builds a new route to modify the status of an entity.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type object.
   *
   * @return \Symfony\Component\Routing\Route
   *   The new Route.
   */
  protected function getEventFormRoutes(EntityTypeInterface $entity_type) {
    /** @var $entity_type EntityTypeInterface */
    if ($entity_type->hasLinkTemplate("event-form")) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate("event-form"));
      // Use the edit form handler, if available, otherwise default.
      $route
        ->setDefaults([
          '_entity_form' => "{$entity_type_id}.event",
          '_title' => "Event {$entity_type->getLabel()}",
        ])
        ->setRequirement('_entity_create_access', "{$entity_type_id}")
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
          'node' => ['type' => 'entity:node'],
          'admin_route' => FALSE,
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
