<?php

namespace Drupal\intercept_event\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\intercept_event\EventManagerInterface;
use Drupal\intercept_event\Service\EventRegistrationEventLock;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Entity with the ReservationLimit constraint.
 */
class RegistrationLimitConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The Intercept event manager.
   *
   * @var \Drupal\intercept_event\EventManagerInterface
   */
  protected $eventManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The event registration event lock service.
   *
   * @var \Drupal\intercept_event\Service\EventRegistrationEventLock
   */
  protected $eventRegistrationEventLock;

  /**
   * Constructs a new ReservationLimitConstraintValidator.
   *
   * @param \Drupal\intercept_event\EventManagerInterface $event_manager
   *   The Intercept event manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(EventManagerInterface $event_manager, AccountProxyInterface $current_user, EventRegistrationEventLock $event_registration_event_lock) {
    $this->eventManager = $event_manager;
    $this->currentUser = $current_user;
    $this->eventRegistrationEventLock = $event_registration_event_lock;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('intercept_event.manager'),
      $container->get('current_user'),
      $container->get('intercept_event.event_registration_event_lock')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity) || $entity->get('status')->value == 'canceled') {
      return;
    }

    $current_user = \Drupal::currentUser();
    if ($current_user->hasPermission('bypass event registration limit') && !$entity->__get('warning')) {
      return;
    }

    $event = $entity->field_event->entity;
    $registrant_id = ($entity->hasField('field_user') && $entity->field_user->target_id) ? $entity->field_user->target_id : NULL;

    // Try to acquire a lock for the event registration.
    // It is possible that multiple requests are trying to
    // register for the same event at the same time. We use
    // a lock here to prevent false negatives in validation.
    if (!$this->eventRegistrationEventLock->acquireLock($event)) {
      // If we can't acquire the lock, validation shouldn't pass.
      $this->context->addViolation($constraint->lockMessage);
      return;
    }

    if ($event_registrations = $this->eventManager->getEventRegistrations($event)) {
      // Get non-canceled event registrations for this user.
      $event_registrations = array_filter($event_registrations, function ($registration) use ($entity, $registrant_id) {
        /** @var \Drupal\intercept_event\Entity\EventRegistrationInterface $registration */
        // Don't include the current registration if it already exists.
        if (!$entity->isNew() && $entity->id() == $registration->id()) {
          return FALSE;
        }
        if ($registration->get('status')->value == 'canceled') {
          return FALSE;
        }
        $field_user_target_id = ($registration->hasField('field_user') && $registration->field_user->target_id) ? $registration->field_user->target_id : NULL;

        // Registrant id will be null if it's a guest registration made by staff.
        $result = (is_null($registrant_id)) ? FALSE : $field_user_target_id == $registrant_id;
        return $result;
      });
    }

    if (!empty($event_registrations)) {
      if ($registrant_id == $entity->getOwnerId()) {
        $this->context->addViolation($constraint->userMessage);
      }
      else {
        $this->context->addViolation($constraint->errorMessage);
      }
    }
  }

}
