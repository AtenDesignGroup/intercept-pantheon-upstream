<?php

namespace Drupal\intercept_bulk_room_reservation\Entity;

use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\intercept_bulk_room_reservation\BulkRoomReservationInterface;

/**
 * Defines the bulk room reservation entity class.
 *
 * @ContentEntityType(
 *   id = "bulk_room_reservation",
 *   label = @Translation("Bulk Room Reservation"),
 *   label_collection = @Translation("Bulk Room Reservations"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\intercept_bulk_room_reservation\BulkRoomReservationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\intercept_bulk_room_reservation\BulkRoomReservationAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\intercept_bulk_room_reservation\Form\BulkRoomReservationForm",
 *       "edit" = "Drupal\intercept_bulk_room_reservation\Form\BulkRoomReservationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "bulk_room_reservation",
 *   data_table = "bulk_room_reservation_field_data",
 *   revision_table = "bulk_room_reservation_revision",
 *   revision_data_table = "bulk_room_reservation_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer bulk room reservation",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "langcode" = "langcode",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "add-form" = "/bulk-room-reservation/add",
 *     "canonical" = "/bulk-room-reservation/{bulk_room_reservation}",
 *     "edit-form" = "/bulk-room-reservation/{bulk_room_reservation}/edit",
 *     "delete-form" = "/bulk-room-reservation/{bulk_room_reservation}/delete",
 *     "collection" = "/bulk-room-reservation"
 *   },
 *   field_ui_base_route = "entity.bulk_room_reservation.settings"
 * )
 */
class BulkRoomReservation extends RevisionableContentEntityBase implements BulkRoomReservationInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new bulk room reservation entity is created, set the uid entity
   * reference to the current user as the creator of the entity.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += ['uid' => \Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage_controller, array $entities) {
    parent::preDelete($storage_controller, $entities);

    $deleteIds = intercept_bulk_room_reservation_delete_ids($entities);
    if (empty($deleteIds)) {
      return;
    }
    $batch = [
      'title' => 'Deleting related room reservations as appropriate',
      'operations' => [
        [
          'Drupal\intercept_bulk_room_reservation\Entity\BulkRoomReservation::batchStart',
          [count($deleteIds)],
        ],
      ],
      'finished' => 'Drupal\intercept_bulk_room_reservation\Entity\BulkRoomReservation::batchFinished',
    ];

    foreach ($deleteIds as $deleteId) {
      $batch['operations'][] = [
        'Drupal\intercept_bulk_room_reservation\Entity\BulkRoomReservation::deleteProcess',
        [$deleteId],
      ];
    }

    batch_set($batch);
  }

  /**
   * Batch callback; initialize the number of room reservations.
   */
  public static function batchStart($total, &$context) {
    $context['results']['room_reservations'] = $total;
  }

  /**
   * Delete event batch processing callback.
   */
  public static function deleteProcess(string $deleteId) {
    $entity = \Drupal::entityTypeManager()->getStorage('room_reservation')->load($deleteId);
    $result = $entity->delete();

  }

  /**
   * Batch finished callback.
   */
  public static function batchFinished($success, $results, $operations) {
    if ($success) {
      if ($results['room_reservations']) {
        \Drupal::service('messenger')->addMessage(\Drupal::translation()
          ->formatPlural($results['room_reservations'], 'Deleted 1 reservation.', 'Deleted @count reservations.'));
      }
      else {
        \Drupal::service('messenger')
          ->addMessage(new TranslatableMarkup('No reservations to delete.'));
      }
    }
    else {
      $error_operation = reset($operations);
      \Drupal::service('messenger')
        ->addMessage(new TranslatableMarkup('An error occurred while processing @operation with arguments : @args'), [
          '@operation' => $error_operation[0],
          '@args' => print_r($error_operation[0]),
        ]);
    }
  }

  /**
   * Get room_reservation entity ids to be deleted.
   *
   * Delete related room reservations if their start date matches no elements
   * of the $dates array as determined by the start and end points in the form.
   *
   * @param array $entities
   *   Array of BulkRoomReservation entities.
   *
   * @return array
   *   Array of RoomReservation entity ids to delete.
   */
  public function getDeleteIds(array $entities) {
    $deleteIds = [];

    foreach ($entities as $key => $entity) {
      foreach ($entity->field_related_room_reserations as $room_reservation) {
        if (in_array($room_reservation, $entity->field_overridden->referencedEntities())) {
          // This room_reservation is overridden; don't delete.
          continue;
        }
        $deleteIds[] = $room_reservation->id();
      }
    }

    return $deleteIds;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the bulk room reservation entity.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Status'))
      ->setDescription(t('A boolean indicating whether the bulk room reservation is enabled.'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Author'))
      ->setDescription(t('The user ID of the bulk room reservation author.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the bulk room reservation was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the bulk room reservation was last edited.'));

    return $fields;
  }

}
