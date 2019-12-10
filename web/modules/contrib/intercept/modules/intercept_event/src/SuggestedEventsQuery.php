<?php

namespace Drupal\intercept_event;

use Drupal\Core\Entity\Query\Sql\Query;

/**
 * SQL Query for suggested Events.
 */
class SuggestedEventsQuery extends Query {

  /**
   * The sort expressions.
   *
   * @var array
   */
  protected $sortExpressions = [];

  /**
   * {@inheritdoc}
   */
  protected function addSort() {
    parent::addSort();
    $this->addSortExpressions();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function compile() {
    parent::compile();
    return $this;
  }

  /**
   * Adds sort expressions.
   */
  public function sortExpression($field_name, $values = []) {
    $this->sortExpressions[$field_name] = $values;
    return $this;
  }

  /**
   * Add sort expressions to the query.
   */
  protected function addSortExpressions() {
    if (!isset($this->tables)) {
      $this->tables = $this->getTables($this->sqlQuery);
    }
    foreach ($this->sortExpressions as $field_name => $values) {
      $this->tables->addField($field_name, 'LEFT', NULL);
      $field_alias = $this->getSqlField($field_name, NULL);
      $expr_alias = $this->sqlQuery->addExpression("(CASE WHEN $field_alias IN (:{$field_name}[]) THEN 1 ELSE 0 END)", NULL, [
        ":{$field_name}[]" => $values,
      ]);
      $this->sqlQuery->groupBy($expr_alias);
      $this->sqlQuery->orderBy($expr_alias, 'DESC');
    }
  }

}
