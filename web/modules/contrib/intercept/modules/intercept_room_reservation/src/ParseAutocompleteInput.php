<?php

namespace Drupal\intercept_room_reservation;

/**
 * Class that parses autocomplete input.
 */
class ParseAutocompleteInput {

  /**
   * Parses a standard autocomplete input element and extracts its target id.
   *
   * @param array $input
   *   The autocomplete input element.
   *
   * @return int
   *   The node id of the entity in the $input.
   */
  public function getIdFromAutocomplete(array $input) {
    $nid = '';

    $inputString = $input['target_id'];

    // Some calls to this method pass actual target_id values.
    if (is_numeric($inputString)) {
      return $inputString;
    }

    // Find the last occurrence of the opening parenthesis in the string.
    $startPos = strrpos($inputString, "(") + 1;

    // Values with commas in them are encapsulated. Detect this and alter
    // length accordingly.
    $length = strlen($inputString) - 1;
    if (substr($inputString, -1, 1) == '"') {
      $length = strlen($inputString) - 2;
    }
    $nid = substr($inputString, strrpos($inputString, "(") + 1, $length - $startPos);

    return $nid;
  }
}
