<?php

namespace Drupal\intercept_event\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\intercept_core\Field\Computed\MethodItemList;
use Drupal\user\UserInterface;

/**
 * Defines the Event Attendance entity.
 *
 * @ingroup intercept_event
 *
 * @ContentEntityType(
 *   id = "event_attendance",
 *   label = @Translation("Event Attendance"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\intercept_event\EventAttendanceListBuilder",
 *     "views_data" = "Drupal\intercept_event\Entity\EventAttendanceViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\intercept_event\Form\EventAttendanceForm",
 *       "add" = "Drupal\intercept_event\Form\EventAttendanceForm",
 *       "scan" = "Drupal\intercept_event\Form\EventAttendanceScanForm",
 *       "scan_guest" = "Drupal\intercept_event\Form\EventAttendanceScanGuestForm",
 *       "scan_lookup" = "Drupal\intercept_event\Form\EventAttendanceScanLookupForm",
 *       "edit" = "Drupal\intercept_event\Form\EventAttendanceForm",
 *       "delete" = "Drupal\intercept_event\Form\EventAttendanceDeleteForm",
 *     },
 *     "access" = "Drupal\intercept_event\EventAccessControlHandler",
 *     "permission_provider" = "Drupal\intercept_event\EventPermissionProvider",
 *     "route_provider" = {
 *       "html" = "Drupal\intercept_event\EventAttendanceHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "event_attendance",
 *   admin_permission = "administer event attendance entities",
 *   entity_keys = {
 *     "id" = "id",
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
 *     "canonical" = "/event-attendance/{event_attendance}",
 *     "add-form" = "/event-attendance/add",
 *     "edit-form" = "/event-attendance/{event_attendance}/edit",
 *     "delete-form" = "/event-attendance/{event_attendance}/delete",
 *     "collection" = "/admin/content/event-attendance",
 *   },
 *   field_ui_base_route = "event_attendance.settings"
 * )
 */
class EventAttendance extends ContentEntityBase implements EventAttendanceInterface {

  use EntityChangedTrait;
  use StringTranslationTrait;

  // Hard-coded target entity constants.
  const TARGET_TYPE = 'node';
  const TARGET_BUNDLE = 'event';
  const EVENT_FIELD = 'field_event';
  const ATTENDEE_FIELD = 'field_user';

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'author' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->t('Event attendance');
  }

  /**
   * {@inheritdoc}
   */
  public function total() {
    return $this->get('field_attendees')->getTotal();
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
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
    return $this->get('author')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('author')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('author', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('author', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttendee() {
    return $this->get(static::ATTENDEE_FIELD) ? $this->get(static::ATTENDEE_FIELD)->entity : $this->getOwner();
  }

  /**
   * {@inheritdoc}
   */
  public function setAttendee(UserInterface $user) {
    $this->set(static::ATTENDEE_FIELD, $user);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEvent() {
    if ($this->hasField(static::EVENT_FIELD)) {
      return $this->get(static::EVENT_FIELD)->entity;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEventId() {
    if ($this->hasField(static::EVENT_FIELD)) {
      return $this->get(static::EVENT_FIELD)->target_id;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title'))
      ->setComputed(TRUE)
      ->setClass(MethodItemList::class)
      ->setSetting('method', 'label')
      ->setReadOnly(TRUE);

    $fields['author'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Recorded by'))
      ->setDescription(new TranslatableMarkup('The user ID of author of the Event Attendance entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Publishing status'))
      ->setDescription(new TranslatableMarkup('A boolean indicating whether the Event Attendance is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the entity was last edited.'));

    return $fields;
  }

}
