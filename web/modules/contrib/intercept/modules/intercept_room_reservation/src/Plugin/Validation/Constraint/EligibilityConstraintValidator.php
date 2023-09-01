<?php

namespace Drupal\intercept_room_reservation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\intercept_core\ReservationManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Entity with the Eligibility constraint.
 */
class EligibilityConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The Intercept reservation manager.
   *
   * @var \Drupal\intercept_core\ReservationManagerInterface
   */
  protected $reservationManager;


  /**
   * Constructs a new EligibilityConstraintValidator.
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
    return new static(
      $container->get('intercept_core.reservation.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity) || $entity->getEntityTypeId() !== 'room_reservation') {
      return;
    }

    $errorMessage = $constraint->getErrorMessage($entity->get('field_room')->target_id);
    if (!empty($errorMessage)){
      $this->context->addViolation($errorMessage);
    }
  }

}
