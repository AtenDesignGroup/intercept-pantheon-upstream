<?php

namespace Drupal\intercept_location;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\node\NodeListBuilder;

/**
 * Builds a list of Location Nodes.
 */
class LocationListBuilder extends NodeListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = parent::buildRow($entity);
    $row['type'] = '';
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = parent::buildHeader();
    $header['type']['data'] = $this->t('ILS ID');
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_query = $this->storage->getQuery()->accessCheck(TRUE);
    $entity_query->condition('type', 'location');
    $header = $this->buildHeader();
    $entity_query->tableSort($header);
    $ids = $entity_query->execute();
    return $this->storage->loadMultiple($ids);
  }

}
