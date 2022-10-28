<?php

namespace Drupal\intercept_dashboard;

/**
 * Filter provider service class interface.
 */
interface FilterProviderInterface {

  /**
   * Retrieve a list of filter options for taxonomy relationships.
   *
   * @param string $vocabulary
   *   The id of the taxonomy vocabulary .
   *
   * @return array
   *   An array of term labels keyed by tid.
   */
  public function getRelatedTermOptions(string $vocabulary);

  /**
   * Retrieve a list of filter options for node relationships.
   *
   * @param string $bundle
   *   The id of the bundle.
   *
   * @return array
   *   An array of node titles keyed by nid.
   */
  public function getRelatedContentOptions(string $bundle);

  /**
   * Retrieve a list of filter options for users.
   *
   * @param int[] $ids
   *   An array of user ids to load.
   *
   * @return array
   *   An array of usernames keyed by uid.
   */
  public function getRelatedUserOptions(array $ids);

  /**
   * Creates a url to the filters with the provide param value removed.
   *
   * @param string $param
   *   The url query parameter to target.
   * @param string $value
   *   (optional) The specific value to remove. Leaving this NULL will result in
   *   the full query parameter being removed.
   *
   * @return \Drupal\Core\Url
   *   The url to the filters with the query parameter removed.
   */
  public function getRemoveUrl(string $param, ?string $value = NULL);

}
