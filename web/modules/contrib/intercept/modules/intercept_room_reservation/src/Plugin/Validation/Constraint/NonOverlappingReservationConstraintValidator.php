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
    if (!isset($entity)) {
      return;
    }

    if ($this->currentUser->hasPermission('bypass room reservation overlap constraints') && !$entity->__get('warning')) {
      return;
    }

    $owner = $entity->getOwner();

    if (!$owner->hasPermission('bypass room reservation overlap constraints') || $entity->__get('warning')) {
      $dates = $entity->field_dates->getValue();
      $room = $entity->field_room->entity;
      if (empty($dates) || empty($room)) {
        return;
      }

      $params = [
        'start' => $dates[0]['value'],
        'end' => $dates[0]['end_value'],
        'rooms' => [$room->id()],
        'duration' => $this->reservationManager->duration($dates[0]['value'], $dates[0]['end_value']),
      ];
      if (!$entity->isNew()) {
        $params['exclude'] = $entity->id();
      }
      $existing_reservations = $this->reservationManager->roomReservationsByNode($params);
      $reservations = !empty($existing_reservations[$room->uuid()]) ? $existing_reservations[$room->uuid()] : [];
      if ($this->reservationManager->hasReservationConflict($reservations, $params)) {
        $this->context->addViolation($constraint->errorMessage);
      }
    }
  }

}
