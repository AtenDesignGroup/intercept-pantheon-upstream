<?php

namespace Drupal\jsonapi_resources_test\Entity\Query;

use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Interface for factories that produce Color Scheme Query objects.
 *
 * In Core, entity queries are usually produced by an instance of:
 * @code \Drupal\Core\Entity\EntityStorageInterface @endcode, but Color Schemes
 * do not actually exist as local Drupal entities, so this interface is a
 * stand-in for the role of that interface.
 */
interface ColorSchemeQueryFactoryInterface {

  /**
   * Creates an empty query for fetching one or more Color Schemes.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   A new, empty instance of a Color Scheme Query.
   */
  public function createQuery(): QueryInterface;

  /**
   * Creates a query that will fetch the Color Scheme having the specified UUID.
   *
   * @param string $uuid
   *   The Universally Unique Identifier (UUID) of the target Color Scheme.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   A new instance of Color Scheme Query that will search for the Color
   *   Scheme having the given UUID when executed.
   */
  public function createQueryForUuid(string $uuid): QueryInterface;

}
