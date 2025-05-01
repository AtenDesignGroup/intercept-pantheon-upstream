<?php

namespace Drupal\votingapi;

/**
 * Provides an interface for a VoteResultFunction plugin.
 *
 * @see \Drupal\votingapi\Annotation\VoteResultFunction
 * @see \Drupal\votingapi\VoteManager
 * @see \Drupal\votingapi\VoteResultFunctionBase
 * @see plugin_api
 */
interface VoteResultFunctionInterface {

  /**
   * Retrieve the label for the voting result.
   *
   * @return string
   *   The translated label
   */
  public function getLabel(): string;

  /**
   * Retrieve the description for the voting result.
   *
   * @return string
   *   The translated description
   */
  public function getDescription(): string;

  /**
   * Performs the calculations on a set of votes to derive the result.
   *
   * @param \Drupal\votingapi\VoteInterface[] $votes
   *   An array of Vote entities.
   *
   * @return float
   *   A result based on the supplied votes.
   */
  public function calculateResult(array $votes): float;

}
