<?php

namespace Drupal\intercept_event\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\intercept_event\EventManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Entity with the ReservationLimit constraint.
 */
class RegistrationEmailLimitConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

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
   * Constructs a new ReservationLimitConstraintValidator.
   *
   * @param \Drupal\intercept_event\EventManagerInterface $event_manager
   *   The Intercept event manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(EventManagerInterface $event_manager, AccountProxyInterface $current_user) {
    $this->eventManager = $event_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('intercept_event.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity) || $entity->get('status')->value == 'canceled') {
      return;
    }

    $event = $entity->field_event->entity;
    $registrant_email = ($entity->hasField('field_guest_email') && $entity->field_guest_email->value) ? $entity->field_guest_email->value : NULL;

    if (!$registrant_email) {
      return;
    }

    if ($event_registrations = $this->eventManager->getEventRegistrations($event)) {
      // Get non-canceled event registrations for this email.
      $event_registrations = array_filter($event_registrations, function ($registration) use ($entity, $registrant_email) {
        /** @var \Drupal\intercept_event\Entity\EventRegistrationInterface $registration */
        // Don't include the current registration if it already exists.
        if (!$entity->isNew() && $entity->id() == $registration->id()) {
          return FALSE;
        }
        if ($registration->get('status')->value == 'canceled') {
          return FALSE;
        }
        $registration_user = $registration->getRegistrant();
        if ($registration_user) {
          $registration_user_email = $registration_user->getEmail();
          if ($registration_user_email == $registrant_email) {
            return TRUE;
          }
        }
        if ($registration->hasField('field_guest_email') && $registration_email = $registration->field_guest_email->value) {
          if ($registration_email == $registrant_email) {
            return TRUE;
          }
        }
        return FALSE;
      });
    }

    if (!empty($event_registrations)) {
      if ($entity->getOwnerId() != 0) {
        $this->context->addViolation($constraint->errorMessage);
      }
      else {
        $this->context->addViolation($constraint->userMessage);
      }
    }
  }

}
