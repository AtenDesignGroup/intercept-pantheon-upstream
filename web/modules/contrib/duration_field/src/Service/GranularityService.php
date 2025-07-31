<?php

namespace Drupal\duration_field\Service;

/**
 * {@inheritdoc}
 */
class GranularityService implements GranularityServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function convertGranularityArrayToGranularityString(array $granularity_array) {
    $granularity = [];

    // Loop through each of the submitted values.
    foreach (array_keys($granularity_array) as $g) {
      // Check if the submitted value evaluates to TRUE.
      if ($granularity_array[$g]) {
        $granularity[] = $g;
      }
    }

    // Build and return the granularity string.
    return implode(':', $granularity);
  }

  /**
   * {@inheritdoc}
   */
  public function convertGranularityStringToGranularityArray($granularity_string) {
    $granularity = $this->getDrupalStatic(__CLASS__ . '::' . __FUNCTION__);
    if (!isset($granularity[$granularity_string])) {
      $granularity[$granularity_string] = [
        'y' => FALSE,
        'm' => FALSE,
        'd' => FALSE,
        'h' => FALSE,
        'i' => FALSE,
        's' => FALSE,
      ];

      foreach (explode(':', $granularity_string) as $key) {
        if (strlen($key)) {
          $granularity[$granularity_string][$key] = TRUE;
        }
      }
    }

    return $granularity[$granularity_string];
  }

  /**
   * {@inheritdoc}
   */
  public function includeGranularityElement($granularity_element, $granularity_string) {
    $granularity = $this->convertGranularityStringToGranularityArray($granularity_string);

    return $granularity[$granularity_element];
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
