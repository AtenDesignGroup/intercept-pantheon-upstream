<?php

namespace Drupal\jsonapi_resources_test\Entity\Query;

use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Default factory for creating entity query adapters for Color Schemes.
 *
 * @noinspection PhpUnused
 */
class ColorSchemeQueryFactory implements ColorSchemeQueryFactoryInterface {

  /**
   * {@inheritdoc}
   */
  public function createQuery(): QueryInterface {
    return new ColorSchemeQuery();
  }

  /**
   * {@inheritdoc}
   */
  public function createQueryForUuid(string $uuid): QueryInterface {
    return $this->createQuery()->condition('uuid', $uuid, '=');
  }

}
