<?php

namespace Drupal\duration_field\Plugin\DataType;

/**
 * Interface for Iso8601String Typed Data objects.
 *
 * @see https://www.iso.org/iso-8601-date-and-time-format.html
 */
interface Iso8601StringInterface {

  /**
   * The ISO 8601 Duration string representing an empty interval.
   *
   * @var string
   */
  const EMPTY_DURATION = 'P0M';

  /**
   * The Regex used to validate an ISO 8601 duration string.
   *
   * @var string
   */
  const DURATION_STRING_PATTERN = '/^P(\d+Y)?(\d+M)?(\d+D)?(T)?(\d+H)?(\d+M)?(\d+S)?$/';

}
