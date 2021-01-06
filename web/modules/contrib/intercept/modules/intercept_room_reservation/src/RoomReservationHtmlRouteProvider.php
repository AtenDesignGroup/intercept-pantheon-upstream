<?php

namespace Drupal\intercept_room_reservation;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\intercept_core\InterceptHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Room reservation entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class RoomReservationHtmlRouteProvider extends InterceptHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if ($delete_route = $this->getRevisionDeleteRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision_delete_form", $delete_route);
    }

    if ($settings_form_route = $this->getSettingsFormRoute($entity_type)) {
      $collection->add("$entity_type_id.settings", $settings_form_route);
    }

    foreach (['cancel', 'approve', 'deny', 'archive', 'request'] as $action) {
      if ($settings_form_route = $this->getUpdateStatusFormRoutes($entity_type, $action)) {
        $collection->add("entity.$entity_type_id.{$action}_form", $settings_form_route);
      }
    }

    return $collection;
  }

  /**
   * Gets the revision delete route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionDeleteRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('revision-delete-form')) {
      $route = new Route($entity_type->getLinkTemplate('revision-delete-form'));
      $route
        ->setDefaults([
          '_form' => '\Drupal\intercept_room_reservation\Form\RoomReservationRevisionDeleteForm',
          '_title' => 'Delete earlier revision',
        ])
        ->setRequirement('_permission', 'delete all room reservation revisions')
        ->setOption('_admin_route', TRUE);

      return $route;
    }
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
      $route = new Route("/admin/structure/intercept/{$entity_type->id()}/settings");
      $route
        ->setDefaults([
          '_form' => 'Drupal\intercept_room_reservation\Form\RoomReservationSettingsForm',
          '_title' => "{$entity_type->getLabel()} settings",
        ])
        ->setRequirement('_permission', $entity_type->getAdminPermission())
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

}
