<?php

namespace Drupal\intercept_core;

use Drupal\Core\Entity\EntityStorageException;

trait EntityUuidConverterTrait {

  /**
   * Convert single entity uuid to id.
   *
   * @param $uuid
   *   Drupal entity uuid.
   * @param $entity_type
   *   Entity type machine name.
   *
   * @return mixed|null
   */
  public function convertUuid($uuid, $entity_type_id) {
    $uuids = $this->convertUuids([$uuid], $entity_type_id);
    return !empty($uuids) ? reset($uuids) : NULL;
  }

  /**
   * Convert array of uuids to array of ids.
   *
   * @param array $uuids
   *   Numeric array of uuids.
   * @param $entity_type_id
   *   Entity type machine name.
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function convertUuids(array $uuids, $entity_type_id) {
    $uuid_key = $this->getUuidKey($entity_type_id);

    $entities = $this->getEntityTypeManager()->getStorage($entity_type_id)->loadByProperties([
      $uuid_key => $uuids,
    ]);

    return !empty($entities) ? array_keys($entities) : [];
  }

  /**
   * Check entity type definition for the actual uuid key.
   *
   * @param $entity_type_id
   *   Entity type machine name.
   *
   * @return bool|string
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getUuidKey($entity_type_id) {
    $entity_type = $this->getEntityTypeManager()->getDefinition($entity_type_id);

    if (!$uuid_key = $entity_type->getKey('uuid')) {
      throw new EntityStorageException("Entity type $entity_type_id does not support UUIDs.");
    }

    return $uuid_key;
  }

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  protected function getEntityTypeManager() {
    return $this->entityTypeManager ?: \Drupal::service('entity_type.manager');
  }
}
