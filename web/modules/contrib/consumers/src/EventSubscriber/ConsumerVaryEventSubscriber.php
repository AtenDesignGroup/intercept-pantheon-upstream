<?php

namespace Drupal\consumers\EventSubscriber;

use Drupal\consumers\MissingConsumer;
use Drupal\consumers\Negotiator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Vary header by consumer.
 */
class ConsumerVaryEventSubscriber implements EventSubscriberInterface {

  /**
   * The consumer id header key.
   *
   * @var string
   */
  const CONSUMER_ID_HEADER = 'X-Consumer-ID';

  /**
   * The consumer negotiator.
   *
   * @var \Drupal\consumers\Negotiator
   */
  protected $negotiator;

  /**
   * ConsumerVaryEventSubscriber constructor.
   *
   * @param \Drupal\consumers\Negotiator $negotiator
   *   The consumer negotiator.
   */
  public function __construct(Negotiator $negotiator) {
    $this->negotiator = $negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => 'onRespond',
    ];
  }

  /**
   * React on response and set the Vary header.
   */
  public function onRespond(ResponseEvent $event) {
    $response = $event->getResponse();

    try {
      $consumer = $this->negotiator->negotiateFromRequest($event->getRequest());
    }
    catch (MissingConsumer $e) {
      // If there's no consumer in the header, and no default consumer then we
      // don't need to add any Vary headers.
      $consumer = FALSE;
    }

    if ($consumer) {
      // Add consumer id to headers.
      $response->headers->set(self::CONSUMER_ID_HEADER, $consumer->getClientId());

      // Add consumer id to vary headers.
      $vary_headers = $response->getVary();
      $vary_headers[] = self::CONSUMER_ID_HEADER;
      $response->setVary($vary_headers);
    }
  }

}
