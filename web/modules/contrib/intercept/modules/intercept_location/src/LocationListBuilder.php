<?php

namespace Drupal\intercept_location;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\node\NodeListBuilder;

class LocationListBuilder extends NodeListBuilder {

  public function buildRow(EntityInterface $entity) {
    $row = parent::buildRow($entity);
    $row['type'] = $entity->field_polaris_id->getString();
    return $row;
  }

  public function buildHeader() {
    $header = parent::buildHeader();
    $header['type']['data'] = $this->t('ILS ID');
    return $header;
  }

  public function load() {
    $entity_query = $this->storage->getQuery();
    $entity_query->condition('type', 'location');
    $header = $this->buildHeader();
    $entity_query->tableSort($header);
    $ids = $entity_query->execute();
    return $this->storage->loadMultiple($ids);
  }

  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    $operations['mapping'] = [
      'title' => $this->t('Mapping'),
      'url' => Url::fromRoute('intercept_location.organization_mapping_form', [
        'node' => $entity->id(),
      ]),
      'query' => [
        'destination' => Url::fromRoute('<current>')->toString(),
      ],
    ];
    return $operations;
  }

}
