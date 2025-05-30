<?php

namespace Drupal\intercept_room_reservation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\intercept_core\ReservationManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Make non-overlapping room reservations constraint.
 */
class NonOverlappingReservationConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The Intercept reservation manager.
   *
   * @var \Drupal\intercept_core\ReservationManagerInterface
   */
  protected $reservationManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ReservationLimitConstraintValidator.
   *
   * @param \Drupal\intercept_core\ReservationManagerInterface $reservation_manager
   *   The Intercept reservation manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(ReservationManagerInterface $reservation_manager, AccountProxyInterface $current_user) {
    $this->reservationManager = $reservation_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('intercept_core.reservation.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    /** @var \Drupal\intercept_room_reservation\Entity\RoomReservationInterface $entity */
    if (!isset($entity)) {
      return;
    }

    $owner = $entity->getOwner();

    if ($entity->__get('warning')) {
      $dates = $entity->field_dates->getValue();
      $room = $entity->field_room->entity;
      if (empty($dates) || empty($room) || empty($dates[0]['value']) || empty($dates[0]['end_value'])) {
        return;
      }

      $params = [
        'start' => $dates[0]['value'],
        'end' => $dates[0]['end_value'],
        'rooms' => [$room->id()],
        'duration' => $this->reservationManager->duration($dates[0]['value'], $dates[0]['end_value']),
      ];
      if (!$entity->isNew()) {
        $params['exclude'] = [$entity->id()];
      }
      if ($entity->uuid()) {
        $params['exclude_uuid'] = [$entity->uuid()];
      }
      $existing_reservations = $this->reservationManager->roomReservationsByNode($params);
      $reservations = !empty($existing_reservations[$room->uuid()]) ? $existing_reservations[$room->uuid()] : [];
      $blocked_dates = $this->reservationManager->getBlockedDates($reservations, $params, $room);

      // We should only care about conflicts if the reservation is of a certain status. Otherwise a user
      // may not be able to cancel their own reservations in case of an existing conflict.
      $conflicting_statuses = ['requested', 'approved'];
      if ($this->reservationManager->hasReservationConflict($blocked_dates, $params) && in_array($entity->field_status->value, $conflicting_statuses)) {
        $this->context->addViolation($constraint->errorMessage);
      }
    }
  }

}
