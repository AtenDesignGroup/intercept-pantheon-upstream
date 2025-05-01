<?php

namespace Drupal\votingapi\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Deriver for Votingapi module.
 *
 * @see \Drupal\node\Plugin\migrate\D6NodeDeriver
 */
class D6VotingApiDeriver extends DeriverBase {

  use MigrationDeriverTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $source = static::getSourcePlugin('d6_vote');
    assert($source instanceof DrupalSqlBase);

    try {
      $source->checkRequirements();
    }
    catch (RequirementsException $e) {
      // Nothing to generate.
      return $this->derivatives;
    }

    try {
      $entity_types = $source->getDatabase()->select('votingapi_vote', 'v')
        ->distinct(TRUE)
        ->fields('v', ['content_type'])
        ->execute()
        ->fetchCol();

      foreach ($entity_types as $entity_type) {
        $bundles = [];
        switch ($entity_type) {
          case 'node':
            $used_node_types_query = $source->getDatabase()
              ->select('node', 'n');
            $used_node_types_query->innerJoin('votingapi_vote', 'v', 'n.nid = v.content_id');
            $used_node_types_query->condition('v.content_type', 'node');
            $used_node_types_query->fields('n', ['type'])
              ->groupBy('n.type');
            $bundles = array_keys($used_node_types_query->execute()
              ->fetchAllAssoc('type', \PDO::FETCH_ASSOC));
            break;

          case 'comment':
            $query = $source->getDatabase()
              ->select('comment', 'c');
            $query->innerJoin('node', 'n', 'c.nid = n.nid');
            $query->innerJoin('votingapi_vote', 'v', 'c.cid = v.content_id');
            $query->condition('v.content_type', 'comment');
            $query->fields('n', ['type'])
              ->groupBy('n.type');
            $bundles = array_keys($query->execute()
              ->fetchAllAssoc('type', \PDO::FETCH_ASSOC));
            break;

        }

        if (!empty($bundles)) {
          // E.g. 'd6_vote:node:article', 'd6_vote:comment:page'.
          foreach ($bundles as $bundle) {
            $derivative_id = implode(PluginBase::DERIVATIVE_SEPARATOR, [
              $entity_type,
              $bundle,
            ]);
            $derivative_definition = $base_plugin_definition;
            $derivative_definition['migration_dependencies']['required'][] = 'd6_' . $entity_type . ':' . $bundle;
            $derivative_definition['source']['entity_type'] = $entity_type;
            $derivative_definition['source']['bundle'] = $bundle;
            $this->derivatives[$derivative_id] = $derivative_definition;
          }
        }
        else {
          // E.g. 'd6_vote:taxonomy_term', 'd6_vote:user'.
          $this->derivatives[$entity_type] = $base_plugin_definition;
          $this->derivatives[$entity_type]['source']['entity_type'] = $entity_type;
        }
      }
    }
    catch (DatabaseExceptionWrapper $e) {
      // Once we begin iterating the source plugin it is possible that the
      // source tables will not exist. This can happen when the
      // MigrationPluginManager gathers up the migration definitions but we do
      // not actually have a Drupal 6 source database.
    }

    return $this->derivatives;
  }

}
