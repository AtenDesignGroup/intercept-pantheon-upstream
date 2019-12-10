<?php

namespace Drupal\intercept_core;

/**
 * Trait for modifiable list builder.
 *
 * @TODO: combine this with EventListBuilderTrait.
 */
trait SettableListBuilderTrait {

  /**
   * Array of entity ids.
   *
   * @var array
   */
  protected $entityIds;

  /**
   * Array of key columns to hide if needed.
   *
   * @var array
   */
  protected $hideColumns = [];

  /**
   * Set the ids to use for specific entities.
   *
   * @param array $ids
   *   An array of entity ids.
   *
   * @return $this
   */
  public function setEntityIds(array $ids) {
    $this->entityIds = $ids;
    return $this;
  }

  /**
   * Override the limit of 50 set in the parent class.
   *
   * @param int $limit
   *   Integer to pass to the query.
   *
   * @return $this
   */
  public function setLimit($limit) {
    $this->limit = $limit;
    return $this;
  }

  /**
   * Override the visible headers and rows.
   *
   * @param array $column_keys
   *   Array of column and header keys.
   *
   * @return $this
   */
  public function hideColumns(array $column_keys) {
    $this->hideColumns = $column_keys;
    return $this;
  }

  /**
   * Process header array and hide keys.
   *
   * @return array
   *   The modified header array.
   */
  protected function hideHeaderColumns($header) {
    foreach ($this->hideColumns as $key) {
      unset($header[$key]);
    }
    return $header;
  }

  /**
   * Process row array and hide keys.
   */
  protected function hideRowColumns($rows) {
    foreach ($this->hideColumns as $key) {
      unset($rows[$key]);
    }
    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    // Override parent to see if we've set the ids first.
    if (isset($this->entityIds)) {
      return $this->entityIds;
    }
    return parent::getEntityIds();
  }

}
