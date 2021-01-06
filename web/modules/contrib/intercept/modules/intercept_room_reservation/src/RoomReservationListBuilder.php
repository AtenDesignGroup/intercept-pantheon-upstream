<?php

namespace Drupal\intercept_room_reservation;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\intercept_core\SettableListBuilderTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of Room reservation entities.
 *
 * @ingroup intercept_room_reservation
 */
class RoomReservationListBuilder extends EntityListBuilder {

  use SettableListBuilderTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new NodeListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, AccountInterface $current_user) {
    parent::__construct($entity_type, $storage);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getOffCanvasAttributes() {
    return [
      'attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'dialog',
        'data-dialog-renderer' => 'off_canvas',
        'data-dialog-options' => Json::encode(['width' => 500]),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $operations['view'] = [
      'title' => $this->t('View'),
      'url' => $this->ensureDestination($entity->toUrl()),
      'weight' => 11,
    ] + $this->getOffCanvasAttributes();
    $operations['edit'] += $this->getOffCanvasAttributes();

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Reservation');
    $header['room'] = $this->t('Room');
    $header['location'] = $this->t('Location');
    $header['user'] = $this->t('User');
    $header['status'] = $this->t('Status');
    $header = array_merge($header, parent::buildHeader());
    return $this->hideHeaderColumns($header);
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\intercept_room_reservation\Entity\RoomReservationInterface $entity */
    $row['name'] = $entity->toLink($entity->getDateRange('UTC'))->toString();
    $row['room'] = $this->getEntityLabel($entity->field_room->entity, $this->t('No room'));
    $row['location'] = $entity->getLocation() ? $entity->getLocation()->link() : '';
    $row['user'] = $this->getEntityLabel($entity->field_user->entity, $this->t('No user'));
    $row['status'] = $entity->field_status->getString();
    $row = array_merge($row, parent::buildRow($entity));
    return $this->hideRowColumns($row);
  }

  /**
   * Loads entity IDs using a pager sorted by the entity id.
   *
   * @return array
   *   An array of entity IDs.
   */
  protected function getEntityIds() {
    // If using SettableListBuilderTrait::setEntityIds then use that.
    if (isset($this->entityIds)) {
      return $this->entityIds;
    }
    // Otherwise override EntityListBuilder::getEntityIds to change sort.
    $query = $this->getStorage()->getQuery()
      ->sort('created', 'DESC');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $build;
  }

  /**
   * Gets the Room Reservation label.
   *
   * @return string
   *   The Room Reservation label.
   */
  private function getEntityLabel(EntityInterface $entity = NULL, $default = '') {
    return $entity ? $entity->toLink()->toString() : $default;
  }

  /**
   * Returns room reservation operations based on the user and current status.
   */
  private function getAllowedStatusChangeOperations(EntityInterface $entity) {
    /** @var \Drupal\intercept_room_reservation\Entity\RoomReservationInterface $entity */
    if ($entity->hasField('field_status')) {
      $status = $entity->field_status->value;
      switch ($status) {
        case 'canceled':
          $operations = ['request', 'archive'];
          break;

        case 'requested':
          $operations = ['cancel', 'archive'];
          break;

        case 'approved':
          $operations = ['cancel', 'deny', 'archive'];
          break;

        case 'denied':
          $operations = ['approve', 'archive'];
          break;
      }
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operation_types = $this->getAllowedStatusChangeOperations($entity);

    $operations = [];

    foreach ($operation_types as $type) {
      if (!$entity->access($type)) {
        continue;
      }
      $operations[$type] = [
        'title' => $this->t('@type', ['@type' => ucwords($type)]),
        'url' => Url::fromRoute("entity.room_reservation.{$type}_form", [
          'room_reservation' => $entity->id(),
          'destination' => Url::fromRoute('<current>')->toString(),
        ]),
        'weight' => '20',
      ];
      if ($type == 'request') {
        $operations[$type]['title'] = $this->t('Rerequest');
      }
    }
    $operations = array_merge($operations, parent::getOperations($entity));
    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $operations;
  }

}
