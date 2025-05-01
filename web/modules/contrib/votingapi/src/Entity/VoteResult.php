<?php

declare(strict_types=1);

namespace Drupal\votingapi\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\votingapi\VoteResultInterface;

/**
 * Defines the VoteResult entity.
 *
 * @ingroup votingapi
 *
 * @ContentEntityType(
 *   id = "vote_result",
 *   label = @Translation("Vote result"),
 *   label_collection = @Translation("Vote results"),
 *   label_singular = @Translation("vote result"),
 *   label_plural = @Translation("vote results"),
 *   label_count = @PluralTranslation(
 *     singular = "@count vote result",
 *     plural = "@count vote results",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\votingapi\VoteResultStorage",
 *     "access" = "Drupal\votingapi\VoteResultAccessControlHandler",
 *     "views_data" = "Drupal\votingapi\Entity\VoteResultViewsData",
 *   },
 *   base_table = "votingapi_result",
 *   entity_keys = {
 *     "id" = "id"
 *   }
 * )
 */
class VoteResult extends ContentEntityBase implements VoteResultInterface {

  /**
   * {@inheritdoc}
   */
  public function getVotedEntityType(): string {
    return $this->get('entity_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setVotedEntityType(string $name): static {
    return $this->set('entity_type', $name);
  }

  /**
   * {@inheritdoc}
   */
  public function getVotedEntityId(): string|int {
    return $this->get('entity_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setVotedEntityId(string|int $id): static {
    return $this->set('entity_id', $id);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(): float {
    return (float) $this->get('value')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue(float $value): static {
    return $this->set('value', $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getValueType(): string {
    return $this->get('value_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setValueType(string $value_type): static {
    return $this->set('value_type', $value_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getFunction(): string {
    return $this->get('function')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFunction(string $function): static {
    return $this->set('function', $function);
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return $this->get('timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime(int $timestamp): static {
    return $this->set('timestamp', $timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('ID'))
      ->setDescription(new TranslatableMarkup('The vote result ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Type'))
      ->setDescription(new TranslatableMarkup('The vote type.'))
      ->setSetting('target_type', 'vote_type')
      ->setReadOnly(TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Entity Type'))
      ->setDescription(new TranslatableMarkup('The type from the voted entity.'))
      ->setSettings([
        'max_length' => 64,
      ])
      ->setRequired(TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Voted entity'))
      ->setDescription(new TranslatableMarkup('The ID from the voted entity'))
      ->setRequired(TRUE);

    $fields['value'] = BaseFieldDefinition::create('float')
      ->setLabel(new TranslatableMarkup('Value'))
      ->setDescription(new TranslatableMarkup('The numeric value of the vote.'))
      ->setRequired(TRUE);

    $fields['value_type'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Value Type'))
      ->setSettings([
        'max_length' => 64,
      ])
      ->setRequired(TRUE);

    $fields['function'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Function'))
      ->setDescription(new TranslatableMarkup('Function to apply to the numbers.'))
      ->setSettings([
        'max_length' => 100,
      ])
      ->setRequired(TRUE);

    $fields['timestamp'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time that the entity was created.'))
      ->setRequired(TRUE);

    return $fields;
  }

}
