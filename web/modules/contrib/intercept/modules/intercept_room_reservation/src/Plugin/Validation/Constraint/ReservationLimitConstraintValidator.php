<?php

namespace Drupal\intercept_room_reservation\Plugin\Validation\Constraint;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\intercept_core\ReservationManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Entity with the ReservationLimit constraint.
 */
class ReservationLimitConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ReservationLimitConstraintValidator.
   *
   * @param \Drupal\intercept_core\ReservationManagerInterface $reservation_manager
   *   The Intercept reservation manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ReservationManagerInterface $reservation_manager, AccountProxyInterface $current_user, ConfigFactoryInterface $config_factory) {
    $this->reservationManager = $reservation_manager;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('intercept_core.reservation.manager'),
      $container->get('current_user'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity) || $entity->getEntityTypeId() !== 'room_reservation') {
      return;
    }
    /** @var \Drupal\intercept_room_reservation\Entity\RoomReservationInterface $entity */
    if ($entity->getNewStatus() != 'requested') {
      return;
    }

    if ($this->currentUser->hasPermission('bypass room reservation limit') && !$entity->__get('warning')) {
      return;
    }

    $registrant = $entity->getReservor();

    $reservations = array_filter($this->reservationManager->currentUserReservations($registrant), function ($reservation) use ($entity) {
      return $reservation->id() != $entity->id();
    });
    $config = $this->configFactory->get('intercept_room_reservation.settings');
    if (count($reservations) >= $config->get('reservation_limit')) {
      $this->context->addViolation($constraint->errorMessage);
    }
  }

}
