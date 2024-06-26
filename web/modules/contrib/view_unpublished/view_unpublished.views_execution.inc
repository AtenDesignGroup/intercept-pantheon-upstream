<?php

/**
 * @file
 * Provide views runtime hooks for view_unpublished.module.
 */

declare(strict_types=1);

use Drupal\node\Entity\NodeType;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_query_substitutions().
 */
function view_unpublished_views_query_substitutions(ViewExecutable $view): array {
  $account = \Drupal::currentUser();
  $substitutions = [];
  foreach (NodeType::loadMultiple() as $type) {
    $type_id = $type->id();
    $substitutions["***VIEWUNPUBLISHED_TYPE_$type_id***"] = (int) $account->hasPermission('view any unpublished ' . $type_id . ' content');
  }
  $substitutions["***VIEWUNPUBLISHED_ANY***"] = (int) $account->hasPermission('view any unpublished content');

  return $substitutions;
}
