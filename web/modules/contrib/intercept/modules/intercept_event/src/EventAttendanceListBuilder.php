<?php

namespace Drupal\intercept_event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Event Attendance entities.
 *
 * @ingroup intercept_event
 */
class EventAttendanceListBuilder extends EntityListBuilder {

  use EventListBuilderTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];
    $this->addEventHeader($header);
    $header['name'] = $this->t('Name');
    $header['count'] = $this->t('Total');
    $header['user'] = $this->t('User');
    $header['author'] = $this->t('Author');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];
    $this->addEventRow($row, $entity);
    $row['name'] = $entity->toLink()->toString();
    $row['count'] = $entity->total();
    $zip = $entity->field_guest_zip_code->getString();
    $link = !empty($zip) ? $this->t('Guest: @zip', ['@zip' => $zip]) : $this->getUserLink($entity);
    $row['user'] = $link;
    $row['author'] = $this->getUserLink($entity, 'author');
    return $row + parent::buildRow($entity);
  }

}
