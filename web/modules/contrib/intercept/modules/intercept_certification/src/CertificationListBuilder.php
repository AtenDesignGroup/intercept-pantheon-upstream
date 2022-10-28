<?php

namespace Drupal\intercept_certification;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of Certification entities.
 *
 * @ingroup intercept_certification
 */
class CertificationListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new BulkRoomReservationListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Certification ID');
    $header['field_room'] = $this->t('Room');
    $header['field_user'] = $this->t('Customer');
    $header['author'] = $this->t('Certified by');
    $header['created'] = $this->t('Created');
    $header['changed'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\intercept_certification\Entity\Certification $entity */
    $row['id'] = $entity->id();
    $row['field_room'] = $entity->getRoom()->label();
    $row['field_user'] = $entity->getCustomer()->label();
    $row['author'] = $entity->getOwner()->label();
    $row['created'] = $this->dateFormatter->format($entity->getCreatedTime());
    $row['changed'] = $this->dateFormatter->format($entity->getChangedTime());
    return $row + parent::buildRow($entity);

  }

  /**
   * Gets an HTML link string to an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being listed.
   * @param string $default
   *   The fallback label string.
   *
   * @return string
   *   The HTML link string to an entity.
   */
  private function getEntityLabel(EntityInterface $entity = NULL, $default = '') {
    return $entity ? $entity->toLink()->toString() : $default;
  }

}
