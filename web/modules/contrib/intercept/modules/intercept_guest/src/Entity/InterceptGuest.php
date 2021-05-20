<?php

namespace Drupal\intercept_guest\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\intercept_guest\InterceptGuestInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the InterceptGuest entity.
 *
 * @ingroup intercept_guest
 *
 * @ContentEntityType(
 *   id = "intercept_guest",
 *   label = @Translation("Intercept Guest"),
 *   label_collection = @Translation("Intercept Guests"),
 *   label_singular = @Translation("Intercept Guest"),
 *   label_plural = @Translation("Intercept Guests"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Intercept Guest",
 *     plural = "@count Intercept Guests",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\intercept_guest\Entity\Controller\InterceptGuestListBuilder",
 *     "form" = {
 *       "add" = "Drupal\intercept_guest\Form\InterceptGuestForm",
 *       "edit" = "Drupal\intercept_guest\Form\InterceptGuestForm",
 *       "delete" = "Drupal\intercept_guest\Form\InterceptGuestDeleteForm",
 *     },
 *     "access" = "Drupal\intercept_guest\InterceptGuestAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "intercept_guest",
 *   fieldable = TRUE,
 *   admin_permission = "administer intercept guest entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/intercept-guest/{intercept_guest}",
 *     "edit-form" = "/intercept-guest/{intercept_guest}/edit",
 *     "delete-form" = "/intercept-guest/{intercept_guest}/delete",
 *     "collection" = "/intercept-guest/list"
 *   },
 *   field_ui_base_route = "intercept_guest.settings",
 * )
 */
class InterceptGuest extends ContentEntityBase implements InterceptGuestInterface {

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Intercept Guest entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Intercept Guest entity.'))
      ->setReadOnly(TRUE);

    // Owner field of the Intercept Guest entity.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User Name'))
      ->setDescription(t('The name of this Intercept Guest owner.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
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
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->getEntityKey('uid');
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->getValue()[0]['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setChangedTime($timestamp) {
    $this->set('changed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTimeAcrossTranslations() {
    return $this->subject->getChangedTimeAcrossTranslations();
  }

}
