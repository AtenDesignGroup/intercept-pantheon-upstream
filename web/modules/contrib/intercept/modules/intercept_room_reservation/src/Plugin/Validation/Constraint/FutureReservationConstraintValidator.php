<?php

namespace Drupal\intercept_room_reservation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\intercept_core\ReservationManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the reservation is in the future.
 */
class FutureReservationConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

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

    // We only want to constrain existing reservations.
    if (!$entity->isNew()) {
      return;
    }

    $dates = $entity->field_dates->getValue();

    if (empty($dates) || empty($dates[0]['value']) || empty($dates[0]['end_value'])) {
      return;
    }

    if (new \DateTime($dates[0]['value']) < new \DateTime()) {
      $this->context->addViolation($constraint->errorMessage);
    }
  }

}
