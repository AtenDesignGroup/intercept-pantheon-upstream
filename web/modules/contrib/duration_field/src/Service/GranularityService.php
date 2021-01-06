<?php

namespace Drupal\duration_field\Service;

/**
 * {@inheritdoc}
 */
class GranularityService implements GranularityServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function convertGranularityArrayToGranularityString(array $granularityArray) {
    $granularities = [];

    // Loop through each of the submitted values.
    foreach (array_keys($granularityArray) as $granularity) {
      // Check if the submitted value evaluates to TRUE.
      if ($granularityArray[$granularity]) {
        // Add the granularity to the granularities to be rendered.
        $granularities[] = $granularity;
      }
    }

    // Build and return the granularity string.
    return implode(':', $granularities);
  }

  /**
   * {@inheritdoc}
   */
  public function convertGranularityStringToGranularityArray($granularityString) {
    $granularities = $this->getDrupalStatic(__CLASS__ . '::' . __FUNCTION__);
    if (!isset($granularities[$granularityString])) {
      $granularities[$granularityString] = [
        'y' => FALSE,
        'm' => FALSE,
        'd' => FALSE,
        'h' => FALSE,
        'i' => FALSE,
        's' => FALSE,
      ];

      foreach (explode(':', $granularityString) as $key) {
        if (strlen($key)) {
          $granularities[$granularityString][$key] = TRUE;
        }
      }
    }

    return $granularities[$granularityString];
  }

  /**
   * {@inheritdoc}
   */
  public function includeGranularityElement($granularityElement, $granularityString) {
    $granularities = $this->convertGranularityStringToGranularityArray($granularityString);

    return $granularities[$granularityElement];
  }

  /**
   * Returns drupal_static().
   *
   * Set as a protected function so it can be overridden for unit tests.
   *
   * @return array
   *   The drupal static array.
   */
  protected function getDrupalStatic($key) {
    $static = &drupal_static($key);

    return $static;
  }

}
