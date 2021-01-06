<?php

namespace Drupal\intercept_equipment\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\intercept_core\Entity\ReservationBase;

/**
 * Defines the Equipment reservation entity.
 *
 * @ingroup intercept_equipment_reservation
 *
 * @ContentEntityType(
 *   id = "equipment_reservation",
 *   label = @Translation("Equipment reservation"),
 *   handlers = {
 *     "storage" = "Drupal\intercept_equipment\EquipmentReservationStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\intercept_equipment\EquipmentReservationListBuilder",
 *     "views_data" = "Drupal\intercept_equipment\Entity\EquipmentReservationViewsData",
 *     "translation" = "Drupal\intercept_equipment\EquipmentReservationTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\intercept_equipment\Form\EquipmentReservationForm",
 *       "add" = "Drupal\intercept_equipment\Form\EquipmentReservationForm",
 *       "edit" = "Drupal\intercept_equipment\Form\EquipmentReservationForm",
 *       "delete" = "Drupal\intercept_equipment\Form\EquipmentReservationDeleteForm",
 *     },
 *     "access" = "Drupal\intercept_equipment\EquipmentReservationAccessControlHandler",
 *     "permission_provider" = "Drupal\intercept_core\ReservationPermissionsProvider",
 *     "route_provider" = {
 *       "html" = "Drupal\intercept_equipment\EquipmentReservationHtmlRouteProvider",
 *       "revision" = "Drupal\intercept_equipment\EquipmentReservationRevisionRouteProvider",
 *       "delete-multiple" = "Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *   },
 *   base_table = "equipment_reservation",
 *   data_table = "equipment_reservation_field_data",
 *   revision_table = "equipment_reservation_revision",
 *   revision_data_table = "equipment_reservation_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer equipment reservation entities",
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
 *     "add-form" = "/equipment-reservation/add",
 *     "collection" = "/admin/content/equipment-reservations",
 *     "canonical" = "/equipment-reservation/{equipment_reservation}",
 *     "edit-form" = "/equipment-reservation/{equipment_reservation}/edit",
 *     "request-form" = "/equipment-reservation/{equipment_reservation}/request",
 *     "delete-form" = "/equipment-reservation/{equipment_reservation}/delete",
 *     "delete-multiple-form" = "/equipment-reservation/delete",
 *     "version-history" = "/equipment-reservation/{equipment_reservation}/revisions",
 *     "revision" = "/equipment-reservation/{equipment_reservation}/revisions/{equipment_reservation_revision}/view",
 *     "revision-revert-form" = "/equipment-reservation/{equipment_reservation}/revisions/{equipment_reservation_revision}/revert",
 *     "revision-delete-form" = "/equipment-reservation/{equipment_reservation}/revisions/{equipment_reservation_revision}/delete",
 *     "translation_revert" = "/admin/structure/equipment_reservation/{equipment_reservation}/revisions/{equipment_reservation_revision}/revert/{langcode}",
 *   },
 *   field_ui_base_route = "equipment_reservation.settings"
 * )
 */
class EquipmentReservation extends ReservationBase implements EquipmentReservationInterface {

  // Hard-coded target entity constants.
  const TARGET_TYPE = 'node';
  const TARGET_BUNDLE = 'equipment';
  const PARENT_FIELD = 'field_equipment';
  const RESERVOR_FIELD = 'field_user';
  const STATUS_FIELD = 'field_status';

  /**
   * {@inheritdoc}
   */
  public static function reservationType() {
    return 'equipment';
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
  public function location() {
    return $this->t('At @location @reservation_type', [
      '@location' => $this->get('field_location')->entity ? $this->get('field_location')->entity->label() : '',
      '@reservation_type' => $this->get(self::PARENT_FIELD)->entity ? $this->get(self::PARENT_FIELD)->entity->label() : '',
    ]);
  }

}
