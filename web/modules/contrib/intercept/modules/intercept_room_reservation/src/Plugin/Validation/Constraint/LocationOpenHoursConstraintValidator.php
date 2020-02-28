<?php

namespace Drupal\intercept_room_reservation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\intercept_core\ReservationManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Reserve within location open hours constraint.
 */
class LocationOpenHoursConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The Intercept reservation manager.
   *
   * @var \Drupal\intercept_core\ReservationManagerInterface
   */
  protected $reservationManager;

  /**
   * Constructs a new NonOverlappingReservationConstraintValidator.
   *
   * @param \Drupal\intercept_core\ReservationManagerInterface $reservation_manager
   *   The Intercept reservation manager.
   */
  public function __construct(ReservationManagerInterface $reservation_manager) {
    $this->reservationManager = $reservation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('intercept_core.reservation.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity)) {
      return;
    }

    $current_user = \Drupal::currentUser();
    if ($current_user->hasPermission('bypass room reservation open hours constraints') && !$entity->__get('warning')) {
      return;
    }

    $owner = $entity->getOwner();

    if (!$owner->hasPermission('bypass room reservation open hours constraints') || $entity->__get('warning')) {
      $dates = $entity->field_dates->getValue();
      $room = $entity->field_room->entity;

      if (empty($dates) || empty($room->entity)) {
        return;
      }

      $params = [
        'start' => $dates[0]['value'],
        'end' => $dates[0]['end_value'],
        'rooms' => [$room->id()],
      ];
      $existing_reservations = $this->reservationManager->roomReservationsByNode($params);
      $reservations = !empty($existing_reservations[$room->uuid()]) ? $existing_reservations[$room->uuid()] : [];
      if ($this->reservationManager->hasOpeningHoursConflict($reservations, $params, $entity->field_room->entity)) {
        $this->context->addViolation($constraint->errorMessage);
      }
    }
  }

}
