<?php

namespace Drupal\intercept_event\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;
use Drupal\intercept_core\DateRangeFormatterTrait;
use Drupal\user\UserInterface;

/**
 * Defines the Event Recurrence entity.
 *
 * @ingroup intercept_event
 *
 * @ContentEntityType(
 *   id = "event_recurrence",
 *   label = @Translation("Event Recurrence"),
 *   handlers = {
 *     "storage" = "Drupal\intercept_event\EventRecurrenceStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\intercept_event\EventRecurrenceListBuilder",
 *     "views_data" = "Drupal\intercept_event\Entity\EventRecurrenceViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\intercept_event\Form\EventRecurrenceForm",
 *       "add" = "Drupal\intercept_event\Form\EventRecurrenceForm",
 *       "edit" = "Drupal\intercept_event\Form\EventRecurrenceForm",
 *       "delete" = "Drupal\intercept_event\Form\EventRecurrenceDeleteForm",
 *     },
 *     "access" = "Drupal\intercept_event\EventAccessControlHandler",
 *     "permission_provider" = "Drupal\intercept_event\EventPermissionProvider",
 *     "route_provider" = {
 *       "html" = "Drupal\intercept_event\EventRecurrenceHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "event_recurrence",
 *   revision_table = "event_recurrence_revision",
 *   revision_data_table = "event_recurrence_field_revision",
 *   admin_permission = "administer event recurrence entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "uuid" = "uuid",
 *     "uid" = "author",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/event-recurrence/{event_recurrence}",
 *     "add-form" = "/event-recurrence/add",
 *     "edit-form" = "/event-recurrence/{event_recurrence}/edit",
 *     "delete-form" = "/event-recurrence/{event_recurrence}/delete",
 *     "version-history" = "/admin/structure/intercept/event_recurrence/{event_recurrence}/revisions",
 *     "revision" = "/admin/structure/intercept/event_recurrence/{event_recurrence}/revisions/{event_recurrence_revision}/view",
 *     "revision_revert" = "/admin/structure/intercept/event_recurrence/{event_recurrence}/revisions/{event_recurrence_revision}/revert",
 *     "revision_delete" = "/admin/structure/intercept/event_recurrence/{event_recurrence}/revisions/{event_recurrence_revision}/delete",
 *     "collection" = "/admin/content/event_recurrence",
 *   },
 *   field_ui_base_route = "event_recurrence.settings"
 * )
 */
class EventRecurrence extends RevisionableContentEntityBase implements EventRecurrenceInterface {

  use EntityChangedTrait;

  use DateRangeFormatterTrait;

  use StringTranslationTrait;

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
    $label = t('Event Recurrence: %label', [
      '%label' => $this->id(),
    ]);
    return $label;
  }

  public function getDate() {
    if (!$date = $this->field_event_rrule->first()) {
      return '';
    }
    return $this->getDateRange($date);
  }

  public function getEvents() {
    if ($this->isNew()) {
      return [];
    }
    return $this->entityTypeManager()->getStorage('node')->loadByProperties([
      'event_recurrence' => $this->id(),
    ]);
  }

  /**
   * Delete all events associated with this recurrence entity.
   */
  public function deleteEvents() {
    $nodes = $this->getEvents();
    $base_node = $this->event->entity;
    $nodes = array_filter($nodes, function($node) use ($base_node) {
      return $base_node->id() != $node->id();
    });
    $this->entityTypeManager()->getStorage('node')->delete($nodes);
    return $nodes;
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the event_recurrence owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  public function getRecurHandler() {
    if (!$this->getRecurField()) {
      return FALSE;
    }
    return $this->getRecurField()->getOccurrenceHandler();
  }

  public function getRecurField() {
    if (!$this->hasField('field_event_rrule')) {
      return FALSE;
    }
    return $this->field_event_rrule->first();
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['author'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Event Recurrence entity.'))
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

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['event'] = \Drupal\Core\Field\BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Base event node'))
      ->setSetting('target_type', 'node')
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
