<?php

namespace Drupal\intercept_room_reservation\Controller;

use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Controller\NodeViewController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RoomReservationRoomController.
 */
class RoomReservationRoomController extends NodeViewController {
  use AjaxHelperTrait;

  /**
   * Creates a NodeViewController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, AccountInterface $current_user, EntityRepositoryInterface $entity_repository, Config $config) {
    parent::__construct($entity_type_manager, $renderer, $current_user, $entity_repository);
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('current_user'),
      $container->get('entity.repository'),
      $container->get('config.factory')->get('intercept_room_reservation.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $node, $view_mode = 'full', $langcode = NULL) {
    if ($this->getRequestWrapperFormat() == 'drupal_dialog.off_canvas') {
      $view_mode = $this->config->get('off_canvas_room_view_mode') ?: $view_mode;
    }
    $build = parent::view($node, $view_mode, $langcode);

    return $build;
  }

}
