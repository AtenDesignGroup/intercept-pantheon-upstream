<?php

namespace Drupal\votingapi;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a vote result entity.
 */
interface VoteResultInterface extends ContentEntityInterface {

  /**
   * Returns the type of entity that the vote was cast on.
   *
   * @return string
   *   The entity type.
   */
  public function getVotedEntityType(): string;

  /**
   * Sets the type of entity that the vote was cast on.
   *
   * @param string $name
   *   The entity type.
   *
   * @return $this
   */
  public function setVotedEntityType(string $name): static;

  /**
   * Returns the ID of the entity that the vote was cast on.
   *
   * @return string|int
   *   The entity ID.
   */
  public function getVotedEntityId(): string|int;

  /**
   * Sets the ID of the entity that the vote was cast on.
   *
   * @param string|int $id
   *   The entity ID.
   *
   * @return $this
   */
  public function setVotedEntityId(string|int $id): static;

  /**
   * Returns the vote value.
   *
   * @return float
   *   The numeric value of the vote.
   */
  public function getValue(): float;

  /**
   * Sets the vote value.
   *
   * @param float $value
   *   The vote value.
   *
   * @return $this
   */
  public function setValue(float $value): static;

  /**
   * Returns the vote value type.
   *
   * @return string
   *   The value type of the vote.
   */
  public function getValueType(): string;

  /**
   * Sets the vote value type.
   *
   * @param string $value_type
   *   The vote value type.
   *
   * @return $this
   */
  public function setValueType(string $value_type): static;

  /**
   * Gets the vote creation timestamp.
   *
   * @return int
   *   Creation timestamp of the vote.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the vote creation timestamp.
   *
   * @param int $timestamp
   *   The vote creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime(int $timestamp): static;

  /**
   * Returns the name of the function to apply to the numbers.
   *
   * @return string
   *   Name of the function to apply.
   */
  public function getFunction(): string;

  /**
   * Sets the function to apply to the numbers.
   *
   * @param string $function
   *   The function name.
   *
   * @return $this
   */
  public function setFunction(string $function): static;

}
