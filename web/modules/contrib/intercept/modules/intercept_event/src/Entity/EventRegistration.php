<?php

namespace Drupal\intercept_event\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\intercept_core\DateRangeFormatterTrait;
use Drupal\intercept_core\Field\Computed\MethodItemList;
use Drupal\intercept_core\Event\EntityStatusChangeEvent;
use Drupal\user\UserInterface;

/**
 * Defines the Event Registration entity.
 *
 * @ingroup intercept_event
 *
 * @ContentEntityType(
 *   id = "event_registration",
 *   label = @Translation("Event Registration"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\intercept_event\EventRegistrationListBuilder",
 *     "views_data" = "Drupal\intercept_event\Entity\EventRegistrationViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\intercept_event\Form\EventRegistrationForm",
 *       "add" = "Drupal\intercept_event\Form\EventRegistrationForm",
 *       "event" = "Drupal\intercept_event\Form\EventRegistrationEventForm",
 *       "cancel" = "Drupal\intercept_event\Form\EventRegistrationCancelForm",
 *       "edit" = "Drupal\intercept_event\Form\EventRegistrationForm",
 *       "register" = "Drupal\intercept_event\Form\RegisterForm",
 *       "delete" = "Drupal\intercept_event\Form\EventRegistrationDeleteForm",
 *     },
 *     "access" = "Drupal\intercept_event\EventRegistrationAccessControlHandler",
 *     "permission_provider" = "Drupal\intercept_event\EventPermissionProvider",
 *     "route_provider" = {
 *       "html" = "Drupal\intercept_event\EventRegistrationHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "event_registration",
 *   admin_permission = "administer event registration entities",
 *   constraints = {
 *     "RegistrationLimit" = {},
 *     "RegistrationEmailLimit" = {},
 *     "RegistrationAllowed" = {},
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "author",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/event-registration/{event_registration}",
 *     "add-form" = "/event-registration/add",
 *     "event-form" = "/event/{node}/registrations/add",
 *     "cancel-form" = "/event-registration/{event_registration}/cancel",
 *     "edit-form" = "/event-registration/{event_registration}/edit",
 *     "delete-form" = "/event-registration/{event_registration}/delete",
 *     "collection" = "/admin/content/event-registration",
 *   },
 *   field_ui_base_route = "event_registration.settings"
 * )
 */
class EventRegistration extends ContentEntityBase implements EventRegistrationInterface {

  use EntityChangedTrait;

  use StringTranslationTrait;

  use DateRangeFormatterTrait;

  // Hard-coded target entity constants.
  const TARGET_TYPE = 'node';
  const TARGET_BUNDLE = 'event';
  const EVENT_FIELD = 'field_event';
  const REGISTRANT_FIELD = 'field_user';

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getTitle();
  }

  /**
   * {@inheritdoc}
   */
  public function getEvent() {
    return $this->getParentEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function getEventId() {
    return $this->getParentId();
  }

  /**
   * {@inheritdoc}
   */
  public function setEventEntity(EntityInterface $event) {
    return $this->setParentEntity($event);
  }

  /**
   * {@inheritdoc}
   */
  public function getParentEntity() {
    return $this->get(static::EVENT_FIELD)->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentId() {
    return $this->get(static::EVENT_FIELD)->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setParentEntity(EntityInterface $event) {
    $this->set(static::EVENT_FIELD, $event);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    if (!$event = $this->get(static::EVENT_FIELD)->entity) {
      return $this->t('Event registration');
    }
    $dates = $event->get('field_date_time')->first();
    if (!$dates || !$dates->get('value') || !$dates->get('end_value')) {
      return '';
    }
    $timezone = 'UTC';
    if ($registrant = $this->getRegistrant()) {
      $timezone = $registrant->getTimeZone();
    }
    $values = [
      '@title' => $event->label(),
      '@date' => $this->getDateRange($dates, $timezone),
    ];
    return !empty($values) ? $this->t('@title @date', $values) : '';
  }

  /**
   * {@inheritdoc}
   */
  public function total() {
    return $this->get('field_registrants')->getTotal();
  }

  /**
   * {@inheritdoc}
   */
  public function getRegistrant() {
    return $this->get(static::REGISTRANT_FIELD) ? $this->get(static::REGISTRANT_FIELD)->entity : $this->getOwner();
  }

  /**
   * {@inheritdoc}
   */
  public function setRegistrant(UserInterface $user) {
    $this->set(static::REGISTRANT_FIELD, $user);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalStatus() {
    if ($this->isNew()) {
      return 'empty';
    }
    return isset($this->original) ? $this->original->getStatus() : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->value;
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
  public function statusHasChanged() {
    if ($this->isNew()) {
      return TRUE;
    }
    return $this->getOriginalStatus() != $this->getStatus();
  }

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
  public function validationWarnings() {
    $this->__set('warning', TRUE);
    $violations = $this->validate();
    $this->__unset('warning');
    return $violations;
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
      ->setLabel(new TranslatableMarkup('Authored by'))
      ->setDescription(new TranslatableMarkup('The user ID of author of the Event Registration entity.'))
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

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Status'))
      ->setDescription(new TranslatableMarkup('The event registration status.'))
      ->setDefaultValue('active')
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'canceled' => 'Canceled',
        'active' => 'Active',
        'waitlist' => 'Waitlist',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    // Make sure that the EventRegistrationField refreshes.
    foreach ($entities as $entity) {
      self::invalidateEventCacheTag($entity);
    }
  }

  /**
   * Invalidate cache tag for an event associated with an event registration.
   */
  public static function invalidateEventCacheTag(EventRegistrationInterface $registration) {
    if ($registration->hasField(static::EVENT_FIELD) && !$registration->{static::EVENT_FIELD}->isEmpty()) {
      $event = $registration->getEvent();
      Cache::invalidateTags(['node:' . $event->id()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    // Make sure that the EventRegistrationField refreshes.
    self::invalidateEventCacheTag($this);

    if ($this->statusHasChanged()) {
      $status_event = new EntityStatusChangeEvent($this, $this->getOriginalStatus(), $this->getStatus());

      // Get the event_dispatcher service and dispatch the event.
      $event_dispatcher = \Drupal::service('event_dispatcher');
      $event_dispatcher->dispatch(EntityStatusChangeEvent::CHANGE, $status_event);
    }

    if ($this->getStatus() == 'canceled') {
      \Drupal::service('intercept_event.manager')->fillEventOpenCapacity($this->getEvent());
    }
    parent::postSave($storage);
  }

}
