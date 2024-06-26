<?php

namespace Drupal\intercept_room_reservation\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\intercept_core\Entity\ReservationBase;
use Drupal\intercept_core\Field\Computed\EntityReferenceFieldItemList;
use Drupal\intercept_core\Field\Computed\MethodItemList;

/**
 * Defines the Room reservation entity.
 *
 * @ingroup intercept_room_reservation
 *
 * @ContentEntityType(
 *   id = "room_reservation",
 *   label = @Translation("Room reservation"),
 *   handlers = {
 *     "storage" = "Drupal\intercept_room_reservation\RoomReservationStorage",
 *     "view_builder" = "Drupal\intercept_room_reservation\RoomReservationViewBuilder",
 *     "list_builder" = "Drupal\intercept_room_reservation\RoomReservationListBuilder",
 *     "views_data" = "Drupal\intercept_room_reservation\Entity\RoomReservationViewsData",
 *     "translation" = "Drupal\intercept_room_reservation\RoomReservationTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\intercept_room_reservation\Form\RoomReservationForm",
 *       "add" = "Drupal\intercept_room_reservation\Form\RoomReservationForm",
 *       "copy" = "Drupal\intercept_room_reservation\Form\RoomReservationForm",
 *       "change_status" = "Drupal\intercept_core\Form\ReservationStatusChangeForm",
 *       "customer_reserve" = "Drupal\intercept_room_reservation\Form\RoomReservationForm",
 *       "delete" = "Drupal\intercept_room_reservation\Form\RoomReservationDeleteForm",
 *       "edit" = "Drupal\intercept_room_reservation\Form\RoomReservationForm",
 *       "cancel" = "Drupal\intercept_room_reservation\Form\RoomReservationCancelForm",
 *       "approve" = "Drupal\intercept_room_reservation\Form\RoomReservationApproveForm",
 *       "deny" = "Drupal\intercept_room_reservation\Form\RoomReservationUpdateStatusForm",
 *       "archive" = "Drupal\intercept_room_reservation\Form\RoomReservationUpdateStatusForm",
 *       "request" = "Drupal\intercept_room_reservation\Form\RoomReservationUpdateStatusForm",
 *     },
 *     "access" = "Drupal\intercept_room_reservation\RoomReservationAccessControlHandler",
 *     "permission_provider" = "Drupal\intercept_core\ReservationPermissionsProvider",
 *     "route_provider" = {
 *       "html" = "Drupal\intercept_room_reservation\RoomReservationHtmlRouteProvider",
 *       "revision" = "Drupal\intercept_room_reservation\RoomReservationRevisionRouteProvider",
 *       "delete-multiple" = "Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *   },
 *   base_table = "room_reservation",
 *   data_table = "room_reservation_field_data",
 *   revision_table = "room_reservation_revision",
 *   revision_data_table = "room_reservation_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer room reservation entities",
 *   constraints = {
 *     "Eligibility" = {},
 *     "FutureReservation" = {},
 *     "LocationOpenHours" = {},
 *     "MaxCapacity" = {},
 *     "MinCapacity" = {},
 *     "NonOverlappingRoomReservation" = {},
 *     "ReservationLimit" = {},
 *     "ReservationMaxDuration" = {},
 *     "StaffRoomPermissions" = {},
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "uuid" = "uuid",
 *     "uid" = "author",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message"
 *   },
 *   links = {
 *     "add-form" = "/room-reservation/add",
 *     "approve-form" = "/room-reservation/{room_reservation}/approve",
 *     "archive-form" = "/room-reservation/{room_reservation}/archive",
 *     "cancel-form" = "/room-reservation/{room_reservation}/cancel",
 *     "canonical" = "/room-reservation/{room_reservation}",
 *     "change-status-form" = "/manage/room-reservations/{room_reservation}/change-status",
 *     "collection" = "/admin/content/room-reservations",
 *     "copy-form" = "/room-reservation/{room_reservation}/copy",
 *     "delete-form" = "/room-reservation/{room_reservation}/delete",
 *     "delete-multiple-form" = "/room-reservation/delete",
 *     "deny-form" = "/room-reservation/{room_reservation}/deny",
 *     "edit-form" = "/room-reservation/{room_reservation}/edit",
 *     "request-form" = "/room-reservation/{room_reservation}/request",
 *     "revision-delete-form" = "/room-reservation/{room_reservation}/revisions/{room_reservation_revision}/delete",
 *     "revision-revert-form" = "/room-reservation/{room_reservation}/revisions/{room_reservation_revision}/revert",
 *     "revision" = "/room-reservation/{room_reservation}/revisions/{room_reservation_revision}/view",
 *     "translation_revert" = "/admin/structure/room_reservation/{room_reservation}/revisions/{room_reservation_revision}/revert/{langcode}",
 *     "version-history" = "/room-reservation/{room_reservation}/revisions",
 *   },
 *   field_ui_base_route = "room_reservation.settings"
 * )
 */
class RoomReservation extends ReservationBase implements RoomReservationInterface {

  // Hard-coded target entity constants.
  const TARGET_TYPE = 'node';
  const TARGET_BUNDLE = 'room';
  const PARENT_FIELD = 'field_room';
  const RESERVOR_FIELD = 'field_user';
  const STATUS_FIELD = 'field_status';

  /**
   * {@inheritdoc}
   */
  public static function reservationType() {
    return 'room';
  }

  /**
   * {@inheritdoc}
   */
  public function getParentEntity() {
    return $this->get(self::PARENT_FIELD)->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentId() {
    return $this->get(self::PARENT_FIELD)->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setParentEntity(EntityInterface $parent) {
    $this->set(self::PARENT_FIELD, $parent);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getReservor() {
    return $this->get(self::RESERVOR_FIELD) ? $this->get(self::RESERVOR_FIELD)->entity : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function cancel() {
    $this->set(self::STATUS_FIELD, 'canceled');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function approve() {
    $this->set(self::STATUS_FIELD, 'approved');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function request() {
    $this->set(self::STATUS_FIELD, 'requested');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function decline() {
    return $this->deny();
  }

  /**
   * {@inheritdoc}
   */
  public function deny() {
    $this->set(self::STATUS_FIELD, 'denied');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function archive() {
    $this->set(self::STATUS_FIELD, 'archived');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function validationWarnings() {
    $this->__set('warning', TRUE);
    $violations = $this->validate();
    $this->__unset('warning');
    return $violations;
  }

  /**
   * {@inheritdoc}
   */
  public function getNotes() {
    return $this->get('notes')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setNotes($notes) {
    $this->set('notes', $notes);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function location() {
    return $this->t('At @location @reservation_type', [
      '@location' => $this->get('room_location')->entity ? $this->get('room_location')->entity->label() : '',
      '@reservation_type' => $this->get(self::PARENT_FIELD)->entity ? $this->get(self::PARENT_FIELD)->entity->label() : '',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['location'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title'))
      ->setComputed(TRUE)
      ->setClass(MethodItemList::class)
      ->setSetting('method', 'location')
      ->setReadOnly(TRUE);

    $fields['room_location'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Location'))
      ->setDescription(new TranslatableMarkup("The related room's location entity."))
      ->setComputed(TRUE)
      ->setClass(EntityReferenceFieldItemList::class)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setTargetEntityTypeId('node')->setTargetBundle('location')
      ->setSetting('target_fields', ['field_room', 'field_location'])
      ->setReadOnly(TRUE);

    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Reservation notes'))
      ->setDescription(new TranslatableMarkup('Describe any additional information about this reservation.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue('')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'settings' => [
          'rows' => 4,
        ],
      ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    $current_user = \Drupal::currentUser();

    if ($this->isNew()) {
      $this->setDefaultStatus();
      // If they've signed the agreement, remove it from their session.
      if (\Drupal::service('current_user')->isAnonymous()) {
        return;
      }
      $temp_store = \Drupal::service('tempstore.private')->get('reservation_agreement');
      if ($temp_store->get('room')) {
        $temp_store->delete('room');
        if ($this->hasField('field_agreement')) {
          $this->field_agreement->setValue(1);
        }
      }
      // Debugging/logging for double-bookings.
      $current_path = \Drupal::service('path.current')->getPath();
      $status = $this->getStatus();
      if ($current_path == '/jsonapi/room_reservation/room_reservation') {
        $this->setRevisionLogMessage('Reserved by Room. Initial status: ' . $status);
      }
      else {
        $this->setRevisionLogMessage('Reserved by Calendar. Initial status: ' . $status);
      }
      // End debugging/logging.
    }
    // Don't set the default status for staff. They may be editing the status.
    elseif ($current_user->hasPermission('bypass room reservation agreement') == FALSE) {
      $this->setDefaultStatus();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if ($this->statusHasChanged()) {
      \Drupal::service('intercept_core.reservation.manager')->notifyStatusChange($this, $this->getOriginalStatus(), $this->getNewStatus());
    }
  }

  /**
   * Set status based on the room being reserved.
   */
  public function setDefaultStatus() {
    if (!$this->hasField(self::PARENT_FIELD)) {
      return;
    }
    if (!$this->get(self::PARENT_FIELD)->isEmpty()) {
      $room = $this->get(self::PARENT_FIELD)->entity;
      $approval_required = $room->field_approval_required->getString();

      $current_user = \Drupal::currentUser();
      if ($current_user->hasPermission('bypass room reservation agreement')) {
        $approval_required = FALSE;
        $is_staff = TRUE;
      }
      else {
        $is_staff = FALSE;
      }

      $current_status = $this->get(self::STATUS_FIELD)->getString();
      $current_path = \Drupal::service('path.current')->getPath();

      // Room reservations automatically get approved if either:
      // A) the room itself doesn't require staff approval of the reservations
      // OR B) the staff member has permission to bypass.
      // We DON'T want to do this, however, on the standard
      // room reservation form for staff.
      if ($current_path == '/room-reservation/add' && $is_staff == TRUE) {
        // Do NOT auto-approve.
      }
      elseif (!$approval_required && $current_status == 'requested') {
        $this->approve();
      }
    }
  }

}
