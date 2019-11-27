<?php

namespace Drupal\Tests\consumers\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * @group consumers
 */
class UpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../drupal-8.4.0.bare.standard.php.gz',
      __DIR__ . '/../../drupal-8.4.0-consumers_installed.php',
    ];
  }

  /**
   * Tests the update path from Consumers 8.x-1.0-beta1 on Drupal 8.4.0.
   */
  public function testUpdatePath() {
    $this->runUpdates();
  }

}
