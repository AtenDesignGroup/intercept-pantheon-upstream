<?php

namespace Drupal\intercept_location_closing;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Location Closing entities.
 *
 * @ingroup intercept_location_closing
 */
class InterceptLocationClosingListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['date'] = $this->t('Date');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\intercept_location_closing\Entity\InterceptLocationClosing */
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.intercept_location_closing.edit_form',
      ['intercept_location_closing' => $entity->id()]
    );
    $start_time = new DrupalDateTime($entity->getStartTime(), 'UTC');
    $row['date'] = date('m-d-Y', $start_time->getTimestamp());
    return $row + parent::buildRow($entity);
  }

}
