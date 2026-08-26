<?php

declare(strict_types=1);

namespace Drupal\jsonapi_resources\ParamConverter;

use Drupal\Component\Uuid\Uuid;
use Drupal\Core\ParamConverter\EntityConverter;
use Symfony\Component\Routing\Route;

/**
 * Parameter converter that accepts either an integer ID or a UUID.
 *
 * The JSON:API URL convention identifies entities by UUID, but custom
 * resource routes shipped by jsonapi_resources historically accepted the
 * integer ID. This converter inspects the placeholder value at request
 * time. When the value matches the UUID pattern it resolves the UUID to the
 * entity ID and then upcasts through core's entity converter, so parameter
 * flags such as `bundle` and `load_latest_revision` apply on both paths.
 * Route authors can opt out by declaring an explicit `converter:` on the
 * parameter.
 *
 * @internal jsonapi_resources maintains no PHP API since its API is the
 *   HTTP API. This class may change at any time and this will break any
 *   dependencies on it.
 */
final class AutoEntityConverter extends EntityConverter {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if (is_string($value) && Uuid::isValid($value)) {
      $entity_type_id = $this->getEntityTypeFromDefaults($definition, $name, $defaults);
      $uuid_key = $this->entityTypeManager->getDefinition($entity_type_id)->getKey('uuid');
      if ($uuid_key) {
        $value = $this->resolveUuidToId($entity_type_id, $uuid_key, $value);
        if ($value === NULL) {
          return NULL;
        }
      }
    }
    return parent::convert($value, $definition, $name, $defaults);
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    // This converter is wired exclusively by ResourceRoutes, which sets an
    // explicit `converter:` on the parameter. ParamConverterManager only
    // consults applies() for parameters without an explicit converter, so
    // returning FALSE here prevents this converter from being auto-selected
    // for entity parameters on every other route across the site.
    return FALSE;
  }

  /**
   * Resolves a UUID to the matching entity ID.
   *
   * @return string|int|null
   *   The entity ID, or NULL when no entity carries the UUID.
   */
  private function resolveUuidToId(string $entity_type_id, string $uuid_key, string $uuid): string|int|null {
    $ids = $this->entityTypeManager->getStorage($entity_type_id)
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition($uuid_key, $uuid)
      ->range(0, 1)
      ->execute();
    $id = reset($ids);
    return $id === FALSE ? NULL : $id;
  }

}
