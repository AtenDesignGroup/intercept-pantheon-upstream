<?php

namespace Drupal\intercept_location_closing\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\UserInterface;

/**
 * Defines the Location Closing entity.
 *
 * @ingroup intercept_location_closing
 *
 * @ContentEntityType(
 *   id = "intercept_location_closing",
 *   label = @Translation("Location Closing"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\intercept_location_closing\InterceptLocationClosingListBuilder",
 *     "views_data" = "Drupal\intercept_location_closing\Entity\InterceptLocationClosingViewsData",
 *     "translation" = "Drupal\intercept_location_closing\InterceptLocationClosingTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\intercept_location_closing\Form\InterceptLocationClosingForm",
 *       "add" = "Drupal\intercept_location_closing\Form\InterceptLocationClosingForm",
 *       "edit" = "Drupal\intercept_location_closing\Form\InterceptLocationClosingForm",
 *       "delete" = "Drupal\intercept_location_closing\Form\InterceptLocationClosingDeleteForm",
 *     },
 *     "access" = "Drupal\intercept_location_closing\InterceptLocationClosingAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\intercept_location_closing\InterceptLocationClosingHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "intercept_location_closing",
 *   data_table = "intercept_location_closing_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer location closing entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/intercept_location_closing/{intercept_location_closing}",
 *     "add-form" = "/admin/structure/intercept_location_closing/add",
 *     "edit-form" = "/admin/structure/intercept_location_closing/{intercept_location_closing}/edit",
 *     "delete-form" = "/admin/structure/intercept_location_closing/{intercept_location_closing}/delete",
 *     "collection" = "/admin/structure/intercept_location_closing",
 *   },
 *   field_ui_base_route = "intercept_location_closing.settings"
 * )
 */
class InterceptLocationClosing extends ContentEntityBase implements InterceptLocationClosingInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStartTime() {
    return $this->get('date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndTime() {
    return $this->get('date')->end_value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocations() {
    return $this->get('location')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->get('message')->value;
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
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
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

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Authored by'))
      ->setDescription(new TranslatableMarkup('The user ID of author of the Location Closing entity.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title'))
      ->setDescription(new TranslatableMarkup('A helpful administrative identifier of the closing.'))
      ->setSettings([
        'max_length' => 100,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['message'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Visitor Message'))
      ->setDescription(new TranslatableMarkup('What visitors will see through the reservation system.'))
      ->setSettings([
        'max_length' => 100,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['date'] = BaseFieldDefinition::create('daterange')
      ->setLabel(new TranslatableMarkup('Closing time'))
      ->setDescription(new TranslatableMarkup('The date/time range for the closing.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'daterange_default',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['location'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Location(s)'))
      ->setDescription(new TranslatableMarkup('The location(s) affected by the closing.'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler_settings',
        [
          'target_bundles' => [
            'location' => 'location',
          ],
        ]
      )
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Published'))
      ->setDescription(new TranslatableMarkup('A boolean indicating whether the closing is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 0,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time that the closing was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the closing was last edited.'));

    return $fields;
  }

}
