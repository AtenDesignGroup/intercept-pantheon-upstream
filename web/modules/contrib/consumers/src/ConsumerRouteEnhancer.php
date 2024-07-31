<?php

namespace Drupal\consumers;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\EnhancerInterface;
use Drupal\jsonapi\Routing\Routes;
use Symfony\Component\HttpFoundation\Request;

/**
 * Adds appropriate cache contexts if a consumer request is made.
 */
class ConsumerRouteEnhancer implements EnhancerInterface {

  /**
   * The cache context by which vary the loaded data.
   *
   * @var string
   */
  const CACHE_CONTEXT = 'url.query_args:consumerId';

  /**
   * The consumer negotiator.
   *
   * @var \Drupal\consumers\Negotiator
   */
  protected $consumerNegotiator;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * ConsumerRouteEnhancer constructor.
   *
   * @param \Drupal\consumers\Negotiator $negotiator
   *   The consumer negotiator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(Negotiator $negotiator, ModuleHandlerInterface $module_handler) {
    $this->consumerNegotiator = $negotiator;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    if (!$this->moduleHandler->moduleExists('jsonapi') ||
      !Routes::isJsonApiRequest($defaults) ||
      !Routes::getResourceTypeNameFromParameters($defaults)
    ) {
      return $defaults;
    }

    if (isset($defaults['entity'])) {
      assert($defaults['entity'] instanceof EntityInterface);
      $defaults['entity']->addCacheContexts([static::CACHE_CONTEXT]);
    }

    return $defaults;
  }

}
