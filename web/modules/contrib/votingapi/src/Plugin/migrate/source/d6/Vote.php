<?php

namespace Drupal\votingapi\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 vote source from database.
 *
 * @MigrateSource(
 *   id = "d6_vote",
 *   source_module = "votingapi"
 * )
 */
class Vote extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('votingapi_vote', 'v')
      ->fields('v');
    foreach (['content_type', 'value_type', 'tag'] as $db_field_name) {
      if (!empty($this->configuration[$db_field_name])) {
        $value = (array) $this->configuration[$db_field_name];
        $query->condition("v.$db_field_name", $value, 'IN');
      }
    }

    if (
      !empty($this->configuration['content_type']) &&
      !empty($this->configuration['bundle'])
    ) {
      $content_type = $this->configuration['content_type'];
      $bundle = $this->configuration['bundle'];
      switch ($content_type) {
        case 'node':
          $query->innerJoin('node', 'n', 'v.content_id = n.nid');
          $query->condition('n.type', $bundle);
          $query->fields('n', ['type']);
          break;

        case 'comment':
          $query->innerJoin('comment', 'c', 'v.content_id = c.cid');
          $query->innerJoin('node', 'n', 'c.nid = n.nid');
          $query->condition('n.type', $bundle);
          $query->fields('n', ['type']);
          break;
      }
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'vote_id' => $this->t('Vote ID'),
      'content_type' => $this->t('Content Type'),
      'content_id' => $this->t('Content ID'),
      'value' => $this->t('Value'),
      'value_type' => $this->t('Value Type'),
      'tag' => $this->t('Tag'),
      'uid' => $this->t('User ID'),
      'timestamp' => $this->t('Timestamp'),
      'vote_source' => $this->t('Vote Source'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['vote_id']['type'] = 'integer';
    $ids['vote_id']['alias'] = 'v';
    return $ids;
  }

}
