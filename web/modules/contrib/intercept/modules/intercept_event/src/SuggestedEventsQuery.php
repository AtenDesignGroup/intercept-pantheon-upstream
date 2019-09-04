<?php

namespace Drupal\intercept_event;

use Drupal\Core\Entity\Query\Sql\Query;

class SuggestedEventsQuery extends Query {

  protected $sortExpressions = [];

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
    // TODO: Refactor this to not be hardcoded.
    $this->sqlQuery->addJoin('LEFT', 'flagging', 'f', "f.flag_id = 'saved_event' AND f.entity_id = base_table.nid");
    $this->sqlQuery->isNull('f.id');
    return $this;
  }

  public function sortExpression($field_name, $values = []) {
    $this->sortExpressions[$field_name] = $values;
    return $this;
  }

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
    // $this->debug();
  }

  private function debug() {
    $query = $this->sqlQuery;
    if (method_exists($query, 'preExecute')) {
      $query->preExecute();
    }
    $sql = (string) $query;
    $quoted = array();
    $connection = \Drupal::database();
    foreach ((array) $query->arguments() as $key => $val) {
      $val = is_array($val) ? join(',', $val) : $connection->quote($val);
      $quoted[$key] = is_null($val) ? 'NULL' : $val;
    }
    $sql = strtr($sql, $quoted);
    ksm($sql);
  }
}

