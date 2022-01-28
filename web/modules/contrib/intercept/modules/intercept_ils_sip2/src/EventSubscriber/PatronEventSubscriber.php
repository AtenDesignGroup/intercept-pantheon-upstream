<?php

namespace Drupal\intercept_ils_sip2\EventSubscriber;

use Drupal\externalauth\Event\ExternalAuthEvents;
use Drupal\user\Entity\Role;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class PatronEventSubscriber.
 */
class PatronEventSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new PatronEventSubscriber object.
   */
  public function __construct() {
    
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $events[ExternalAuthEvents::REGISTER] = ['onRegister'];

    $events[ExternalAuthEvents::LOGIN] = ['onLogin'];

    return $events;
  }

  /**
   *
   */
  public function onLogin($event) {
    // Currently not actually triggered with this configuration.
  }

  /**
   * This method is called whenever the routing.route_dynamic event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function onRegister($event) {
    $user = $event->getAccount();
    $data = $event->getData();

    // Give user customer role.
    $this->ensureRole($user);
  }

  /**
   *
   */
  private function ensureRole(UserInterface $user) {
    if (!$role = Role::load('intercept_registered_customer')) {
      return FALSE;
    }
    if (!$user->hasRole('intercept_registered_customer')) {
      $user->addRole('intercept_registered_customer');
      $user->save();
    }
  }

}
