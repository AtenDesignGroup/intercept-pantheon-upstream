<?php

namespace Drupal\duration_field\Service;

/**
 * Defines a granularity form element that works with granularity strings.
 *
 * @see Drupal\duration_field\Plugin\DataType\GranularityStringData
 */
interface GranularityServiceInterface {

  /**
   * Converts a granularity array to a granularity string.
   *
   * @param array $granularityArray
   *   An array containing the following keys. The value of each key will be
   *   evaulated to TRUE or FALSE.
   *   - y (years)
   *   - m (months)
   *   - d (days)
   *   - h (hours)
   *   - i (minutes)
   *   - s (seconds)
   *   TRUE values mean the element should be included as part of the
   *   granularity.
   *
   * @return string
   *   The array converted to a granularity string.
   */
  public function convertGranularityArrayToGranularityString(array $granularityArray);

  /**
   * Converts a granularity string to a granularity array.
   *
   * @param string $granularityString
   *   The granularity string.
   *
   * @return array
   *   An array containing the following keys. The value of each key will be
   *   either TRUE or FALSE.
   *   - y (years)
   *   - m (months)
   *   - d (days)
   *   - h (hours)
   *   - i (minutes)
   *   - s (seconds)
   *   TRUE values mean the element should be included as part of the
   *   granularity.
   */
  public function convertGranularityStringToGranularityArray($granularityString);

  /**
   * Test if the given granularity element should be included.
   *
   * Inclusion is determined based on the given granularity string.
   *
   * @param string $granularityElement
   *   The granularity element to test for. Values can be:
   *   - y (years)
   *   - m (months)
   *   - d (days)
   *   - h (hours)
   *   - i (minutes)
   *   - s (seconds)
   * @param string $granularityString
   *   The granularity string to test against.
   *
   * @return bool
   *   TRUE if the given granularity should be inclued, FALSE otherwise.
   */
  public function includeGranularityElement($granularityElement, $granularityString);

}
