<?php

namespace Drupal\intercept_room_reservation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\intercept_core\ReservationManagerInterface;
use Drupal\intercept_room_reservation\Entity\RoomReservation;
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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new NonOverlappingReservationConstraintValidator.
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
    $dates = $entity->field_dates->getValue();

    // If it's a customer account and it's an existing reservation...
    // ...with a start date that is in the past, it can't be edited.
    $roles = $this->currentUser->getRoles();
    if (in_array('intercept_registered_customer', $roles) && !$entity->isNew()) {
      // Find the original, unmodified room reservation entity.
      // We need to find the start date there, rather than what they're suggesting.
      // $original_reservation = RoomReservation::load($entity->id());
      if (isset($entity->original)) {
        $original_reservation = $entity->original;
      }
      else {
        $original_reservation = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId())->loadRevision($entity->getLoadedRevisionId());
      }
      $timezone = new \DateTimeZone('UTC');
      if (new \DateTime($original_reservation->getStartDate(), $timezone) < new \DateTime('now', $timezone)) {
        $this->context->addViolation('This reservation has already started and can no longer be edited.');
      }
    }
    // Alleviate this constraint for staff trying to edit existing reservations.
    elseif (in_array('intercept_staff', $roles)) {
      if (!$entity->isNew()) {
        return;
      }
    }

    if (empty($dates) || empty($dates[0]['value']) || empty($dates[0]['end_value'])) {
      return;
    }

    // Event dates are stored in UTC, so we need to compare to the current time in UTC.
    $timezone = new \DateTimeZone('UTC');
    if (new \DateTime($dates[0]['value'], $timezone) < new \DateTime('now', $timezone)) {
      $this->context->addViolation($constraint->errorMessage);
    }
  }

}
