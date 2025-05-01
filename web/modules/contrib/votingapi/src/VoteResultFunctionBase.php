<?php

declare(strict_types=1);

namespace Drupal\votingapi;

use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for plugins which provide a function to compute the vote result.
 */
abstract class VoteResultFunctionBase extends PluginBase implements VoteResultFunctionInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
    return $this->t($this->pluginDefinition['label']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
    return $this->t($this->pluginDefinition['description']);
  }

  /**
   * {@inheritdoc}
   */
  abstract public function calculateResult(array $votes): float;

}
