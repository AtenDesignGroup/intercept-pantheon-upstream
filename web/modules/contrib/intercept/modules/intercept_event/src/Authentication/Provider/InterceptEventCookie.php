<?php

namespace Drupal\intercept_event\Authentication\Provider;

use Drupal\Core\Database\Connection;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\user\Authentication\Provider\Cookie;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Drupal\Core\Session\SessionConfigurationInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Cookie based authentication provider.
 */
class InterceptEventCookie extends Cookie {

  use StringTranslationTrait;

  /**
   * The session configuration.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface
   */
  protected $sessionConfiguration;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new cookie authentication provider.
   *
   * @param \Drupal\Core\Session\SessionConfigurationInterface $session_configuration
   *   The session configuration.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Messenger\MessengerInterface|null $messenger
   *   The messenger.
   */
  public function __construct(SessionConfigurationInterface $session_configuration, Connection $connection, MessengerInterface $messenger = NULL) {
    $this->sessionConfiguration = $session_configuration;
    $this->connection = $connection;
    $this->messenger = $messenger;
    if ($this->messenger === NULL) {
      @trigger_error('The MessengerInterface must be passed to ' . __NAMESPACE__ . '\Cookie::__construct(). It was added in drupal:9.2.0 and will be required before drupal:10.0.0.', E_USER_DEPRECATED);
      $this->messenger = \Drupal::messenger();
    }
  }

  /**
   * Adds a query parameter to check successful log in redirect URL.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The Event to process.
   */
  public function addCheckToUrl(ResponseEvent $event) {
    $response = $event->getResponse();
    // Act if we're on the event eval login route.
    if ($response instanceof RedirectResponse && $event->getRequest()->hasSession()) {
      if ($event->getRequest()->getSession()->has('check_logged_in')) {
        if ($event->getRequest()->get('_route') == 'intercept_event.reset.login') {
          $requestParams = $event->getRequest()->query->all();
          unset($requestParams['destination']);
          $event->getRequest()->getSession()->remove('check_logged_in');
          $url = $response->getTargetUrl();
          $options = UrlHelper::parse($url);
          $options['query']['check_logged_in'] = '1';
          $options['query'] = array_merge($options['query'], $requestParams);
          $url = $options['path'] . '?' . UrlHelper::buildQuery($options['query']);
          if (!empty($options['#fragment'])) {
            $url .= '#' . $options['#fragment'];
          }
          // In the case of trusted redirect, we have to update the list of
          // trusted URLs because here we've just modified its target URL
          // which is in the list.
          if ($response instanceof TrustedRedirectResponse) {
            $response->setTrustedTargetUrl($url);
          }
          $response->setTargetUrl($url);
        }
        parent::addCheckToUrl($event);
      }
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['addCheckToUrl', -1000];
    return $events;
  }
}
