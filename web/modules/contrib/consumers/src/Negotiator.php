<?php

namespace Drupal\consumers;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Utility\Error;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Extracts the consumer information from the given context.
 *
 * @internal
 */
class Negotiator {

  /**
   * A service closure around the logger instance.
   *
   * @var \Closure
   */
  protected $logger;

  /**
   * Protected requestStack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The default consumer.
   *
   * @var \Drupal\consumers\Entity\ConsumerInterface
   */
  protected $defaultConsumer;

  /**
   * Instantiates a new Negotiator object.
   */
  public function __construct(RequestStack $request_stack, \Closure $logger, EntityTypeManagerInterface $entityTypeManager, CacheBackendInterface $cache) {
    $this->requestStack = $request_stack;
    $this->logger = $logger;
    $this->entityTypeManager = $entityTypeManager;
    $this->cache = $cache;
  }

  /**
   * Obtains the consumer from the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\consumers\Entity\ConsumerInterface|null
   *   The consumer.
   *
   * @throws \Drupal\consumers\MissingConsumer
   */
  protected function doNegotiateFromRequest(Request $request) {
    // There are several ways to negotiate the consumer:
    // 1. Via a custom header.
    $consumer_id = $request->headers->get('X-Consumer-ID');
    if (!$consumer_id) {
      // 2. Via a query string parameter.
      $consumer_id = $request->query->get('consumerId');
      if (!$consumer_id && $request->query->has('_consumer_id')) {
        $this->logger()->warning('The "_consumer_id" query string parameter is deprecated and it will be removed in the next major version of the module, please use "consumerId" instead.');
        $consumer_id = $request->query->get('_consumer_id');
      }
    }
    if ($consumer_id) {
      try {
        $results = $this->entityTypeManager->getStorage('consumer')->loadByProperties(['client_id' => $consumer_id]);
        /** @var \Drupal\consumers\Entity\ConsumerInterface $consumer */
        $consumer = !empty($results) ? reset($results) : $results;
      }
      catch (EntityStorageException $exception) {
        // Backwards compatibility of error logging. See
        // https://www.drupal.org/node/2932520. This can be removed when we no
        // longer support Drupal < 10.1.
        if (version_compare(\Drupal::VERSION, '10.1', '>=')) {
          Error::logException($this->logger(), $exception);
        }
        else {
          // @phpstan-ignore-next-line
          watchdog_exception('consumers', $exception);
        }
      }
    }
    if (empty($consumer)) {
      $consumer = $this->loadDefaultConsumer();
    }
    return $consumer;
  }

  /**
   * Obtains the client ID from the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return string
   *   The consumer client ID.
   *
   * @throws \Drupal\consumers\MissingConsumer
   */
  protected function doNegotiateClientIdFromRequest(Request $request) {
    // There are several ways to negotiate the consumer:
    // 1. Via a custom header.
    $consumer_id = $request->headers->get('X-Consumer-ID');
    if (!$consumer_id) {
      // 2. Via a query string parameter.
      $consumer_id = $request->query->get('consumerId');
      if (!$consumer_id && $request->query->has('_consumer_id')) {
        $this->logger()->warning('The "_consumer_id" query string parameter is deprecated and it will be removed in the next major version of the module, please use "consumerId" instead.');
        $consumer_id = $request->query->get('_consumer_id');
      }
    }
    if ($consumer_id) {
      // Check the client ID exists.
      $row_count = $this->entityTypeManager->getStorage('consumer')->getQuery()
        ->accessCheck(TRUE)
        ->condition('client_id', $consumer_id)
        ->count()
        ->execute();
      if ($row_count > 0) {
        return $consumer_id;
      }
    }

    return $this->getDefaultClientId();
  }

  /**
   * Gets the client ID from the default consumer.
   *
   * @return string
   *   The default client ID.
   *
   * @throws \Drupal\consumers\MissingConsumer
   */
  private function getDefaultClientId() {
    $cache_data = $this->cache->get('consumers:default_client_id');
    if ($cache_data === FALSE) {
      $consumer = $this->loadDefaultConsumer();
      $client_id = $consumer->getClientId();
      $this->cache->set('consumers:default_client_id', $client_id, CacheBackendInterface::CACHE_PERMANENT, $consumer->getCacheTags());
    }
    else {
      $client_id = $cache_data->data;
    }
    return $client_id;
  }

  /**
   * Obtains the consumer from the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request|null $request
   *   The request object to inspect for a consumer. Set to NULL to use the
   *   current request.
   *
   * @return \Drupal\consumers\Entity\ConsumerInterface|null
   *   The consumer.
   *
   * @throws \Drupal\consumers\MissingConsumer
   */
  public function negotiateFromRequest(?Request $request = NULL) {
    // If the request is not provided, use the request from the stack.
    $request = $request ? $request : $this->requestStack->getCurrentRequest();
    $consumer = $this->doNegotiateFromRequest($request);
    $request->attributes->set('consumer_id', $consumer->getClientId());
    return $consumer;
  }

  /**
   * Obtains the consumer client ID from the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request|null $request
   *   The request object to inspect for a consumer. Set to NULL to use the
   *   current request.
   *
   * @return string
   *   The consumer client ID.
   *
   * @throws \Drupal\consumers\MissingConsumer
   */
  public function negotiateClientIdFromRequest(?Request $request = NULL) {
    // If the request is not provided, use the request from the stack.
    $request = $request ? $request : $this->requestStack->getCurrentRequest();
    $client_id = $this->doNegotiateClientIdFromRequest($request);
    $request->attributes->set('consumer_id', $client_id);
    return $client_id;
  }

  /**
   * Finds and loads the default consumer.
   *
   * @return \Drupal\consumers\Entity\ConsumerInterface
   *   The consumer entity.
   *
   * @throws \Drupal\consumers\MissingConsumer
   */
  protected function loadDefaultConsumer() {
    if (!empty($this->defaultConsumer)) {
      return $this->defaultConsumer;
    }

    $storage = $this->entityTypeManager->getStorage('consumer');
    // Find the default consumer.
    $results = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('is_default', TRUE)
      ->execute();
    $consumer_id = reset($results);
    if (!$consumer_id) {
      // Throw if there is no default consumer.
      throw new MissingConsumer('Unable to find the default consumer.');
    }
    $this->defaultConsumer = $storage->load($consumer_id);

    return $this->defaultConsumer;
  }

  /**
   * Gets the logger.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger.
   */
  private function logger(): LoggerInterface {
    return ($this->logger)();
  }

}
