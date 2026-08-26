<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi_resources\Kernel;

use Drupal\jsonapi\IncludeResolver;

/**
 * Include resolver decorator that counts ::resolve calls.
 *
 * Extends "IncludeResolver" only to satisfy the constructor type hint of
 * "ResourceResponseFactory"; every call is delegated to the decorated
 * resolver.
 */
final class CountingIncludeResolver extends IncludeResolver {

  /**
   * The number of times ::resolve has been called.
   */
  public int $resolveCalls = 0;

  public function __construct(
    private readonly IncludeResolver $inner,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function resolve($data, $include_parameter) {
    $this->resolveCalls++;
    return $this->inner->resolve($data, $include_parameter);
  }

}
