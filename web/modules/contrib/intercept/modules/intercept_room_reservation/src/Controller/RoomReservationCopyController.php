<?php

namespace Drupal\intercept_room_reservation\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\intercept_room_reservation\Entity\RoomReservation;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Intercept Room Reservation routes.
 */
class RoomReservationCopyController extends ControllerBase {

  /**
   * Current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * Builds the response.
   */
  public function build(string $room_reservation) {
    $form = $this->entityFormBuilder->getForm($this->cloneify($room_reservation));
    return $form;
  }

  /**
   * Create an event node clone with certain changes.
   *
   * @param \Drupal\intercept_room_reservation\Entity\RoomReservation $room_reservation
   *   The Room Reservation to clone.
   *
   * @return \Drupal\intercept_room_reservation\Entity\RoomReservation
   *   The cloned Room Reservation.
   */
  public function cloneify(string $room_reservation) {
    $existingRoomReservation = $this->entityTypeManager
      ->getStorage('room_reservation')->load($room_reservation);
    $newRoomReservation = $existingRoomReservation->createDuplicate();
    // Unset a couple of fields.
    foreach (['vid', 'field_dates'] as $field) {
      $newRoomReservation->set($field, NULL);
    }

    $newRoomReservation->setOwnerId($this->currentUser->id());
    return $newRoomReservation;
  }

}
