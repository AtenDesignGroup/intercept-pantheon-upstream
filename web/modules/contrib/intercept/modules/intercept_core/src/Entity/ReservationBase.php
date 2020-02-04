<?php

namespace Drupal\intercept_core\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\intercept_core\Field\Computed\FileFieldItemList;
use Drupal\intercept_core\Field\Computed\MethodItemList;
use Drupal\intercept_core\Utility\Dates;
use Drupal\user\UserInterface;

/**
 * Base class for Equipment and Room Reservations.
 */
abstract class ReservationBase extends RevisionableContentEntityBase {

  use EntityChangedTrait;

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
    $timezone = drupal_get_user_timezone();
    return $this->getDateRange($timezone);
  }

  /**
   * {@inheritdoc}
   */
  public static function reservationType() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDateRange($timezone = 'UTC') {
    if (!$this->getStartDate() || !$this->getEndDate()) {
      return '';
    }
    $values = [];
    $from_date = $this->getStartDate();
    $values['@date'] = $from_date->format('F j, Y', ['timezone' => $timezone]);
    $values['@time_start'] = $from_date->format('h:i A', ['timezone' => $timezone]);

    $to_date = $this->getEndDate();
    $values['@time_end'] = $to_date->format('h:i A', ['timezone' => $timezone]);
    return !empty($values) ? $this->t('@date from @time_start to @time_end', $values) : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getStartDate() {
    return $this->get('field_dates')->start_date;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndDate() {
    return $this->get('field_dates')->end_date;
  }

  /**
   * {@inheritdoc}
   */
  public function getDuration() {
    if ($this->hasBothDates()) {
      return Dates::duration($this->getStartDate(), $this->getEndDate());
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getInterval() {
    if ($this->getDuration() > 0) {
      $int = Dates::interval($this->getStartDate(), $this->getEndDate());
      return $int;
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getLocation() {
    $type = $this->reservationType();
    return $this->get("{$type}_location")->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalStatus() {
    return isset($this->original) ? $this->original->field_status->getString() : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getNewStatus() {
    return $this->field_status->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function statusHasChanged() {
    if ($this->isNew()) {
      return TRUE;
    }
    return $this->getOriginalStatus() != $this->getNewStatus();
  }

  /**
   * {@inheritdoc}
   */
  public function location() {
    $type = $this->reservationType();
    if ($type == 'room') {
      return $this->t('At @location @reservation_type', [
        '@location' => $this->get("{$type}_location")->entity ? $this->get("{$type}_location")->entity->label() : '',
        '@reservation_type' => $this->get("field_{$type}")->entity ? $this->get("field_{$type}")->entity->label() : '',
      ]);
    }
    elseif ($type == 'equipment') {
      return $this->t('At @location @reservation_type', [
        '@location' => $this->get("field_location")->entity ? $this->get("field_location")->entity->label() : '',
        '@reservation_type' => $this->get("field_{$type}")->entity ? $this->get("field_{$type}")->entity->label() : '',
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision-revert-form' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    if ($rel === 'revision-delete-form' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    if (!empty($this->original) && !isset($this->original->values['field_status'])) {
      // Equipment reservations don't have status.
      $this->setNewRevision(TRUE);
    }
    elseif (!empty($this->original) && !$this->original->get('field_status')->equals($this->get('field_status'))) {
      $this->setNewRevision(TRUE);
    }
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);

    $is_new_revision = $this->isNewRevision();
    // @see \Drupal\media\Entity\Media::preSaveRevision()
    if (!$is_new_revision && isset($this->original) && empty($record->revision_log_message)) {
      $record->revision_log_message = $this->original->revision_log_message->value;
    }

    if ($is_new_revision) {
      $record->revision_created = \Drupal::time()->getRequestTime();
      $record->revision_user = \Drupal::currentUser()->id();
    }
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
  public function getRegistrant() {
    return $this->get('field_user') ? $this->get('field_user')->entity : FALSE;
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
   * Callback for base title field.
   */
  public function title() {
    return $this->label();
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
      ->setDescription(new TranslatableMarkup('The user ID of author of the @label entity.', [
        '@label' => $entity_type->getLabel(),
      ]))
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

    $reservation_type = static::reservationType();

    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(new TranslatableMarkup('Image'))
      ->setDescription(new TranslatableMarkup("The related @label entity's image.", [
        '@label' => $reservation_type,
      ]))
      ->setComputed(TRUE)
      ->setClass(FileFieldItemList::class)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('target_fields', [
        "field_$reservation_type",
        'image_primary',
        'field_media_image',
      ])
      ->setReadOnly(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Publishing status'))
      ->setDescription(new TranslatableMarkup('A boolean indicating whether the @label is published.', [
        '@label' > $entity_type->getLabel(),
      ]))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Revision translation affected'))
      ->setDescription(new TranslatableMarkup('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * Checks that a reservation has both a start and end date.
   *
   * @return bool
   *   Whether the reservation has both a start and end date.
   */
  private function hasBothDates() {
    return $this->getStartDate() && $this->getEndDate();
  }

}
