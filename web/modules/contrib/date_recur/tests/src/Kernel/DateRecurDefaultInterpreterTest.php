<?php

declare(strict_types=1);

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests default installed interpreter.
 *
 * @group date_recur
 */
class DateRecurDefaultInterpreterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime',
    'datetime_range',
    'date_recur',
    'field',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'date_recur']);
  }

  /**
   * Tests interpreter config is installed.
   */
  public function testDefaultInterpreter(): void {
    $config = \Drupal::config('date_recur.interpreter.default_interpreter');
    // Values will be an empty array if it doesn't exist.
    $values = $config->get();
    static::assertEquals('default_interpreter', $values['id']);
  }

}
