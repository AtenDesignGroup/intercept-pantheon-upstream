<?php

namespace Drupal\Tests\duration_field\Unit\Service;

use Drupal\duration_field\Service\GranularityService as GranularityServiceBase;

/**
 * Overrides GranularityService, used for UnitTests.
 */
class GranularityService extends GranularityServiceBase {

  /**
   * {@inheritdoc}
   */
  protected function getDrupalStatic($key) {
    return [];
  }

}
