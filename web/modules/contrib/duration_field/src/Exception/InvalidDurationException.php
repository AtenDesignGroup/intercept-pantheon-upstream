<?php

namespace Drupal\duration_field\Exception;

use Exception;

/**
 * Exception thrown when a duration is not a valid ISO 8601 duration string.
 */
class InvalidDurationException extends Exception {}
