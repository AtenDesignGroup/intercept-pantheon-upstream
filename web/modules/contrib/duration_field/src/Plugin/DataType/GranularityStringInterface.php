<?php

namespace Drupal\duration_field\Plugin\DataType;

/**
 * Interface for Granularity String typed data objects.
 *
 * A granularity string contains any or all of the following keys:
 *   - y (years)
 *   - m (months)
 *   - d (days)
 *   - h (hours)
 *   - i (minutes)
 *   - s (seconds)
 *
 * Keys are separated by colons. The presence of a key means that degree of
 * granularity is relevant. For example, a granularity string of y:m:d
 * has keys for year, month and day, and therefore any time elements using that
 * granularity would collect values for year, month and day. A granularity of
 * y:s would indicate a granularity of years and seconds. An empty string means
 * no granularity. Full granularity is y:m:d:h:i:s.
 */
interface GranularityStringInterface {

  /**
   * The Regex used to validate a granularity string.
   *
   * @var string
   */
  const GRANULARITY_STRING_PATTERN = '/^([yYmMdYhHiIsS]:)?([yYmMdYhHiIsS]:)?([yYmMdYhHiIsS]:)?([yYmMdYhHiIsS]:)?([yYmMdYhHiIsS]:)?([yYmMdYhHiIsS]:)?$/';

}
