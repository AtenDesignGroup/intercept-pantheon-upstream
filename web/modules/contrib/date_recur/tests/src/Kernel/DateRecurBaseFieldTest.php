<?php

declare(strict_types=1);

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\date_recur_entity_test\Entity\DrEntityTestBasic;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests base fields.
 *
 * @group date_recur
 */
final class DateRecurBaseFieldTest extends KernelTestBase {

  protected static $modules = [
    'date_recur_entity_test',
    'entity_test',
    'datetime',
    'datetime_range',
    'date_recur',
    'field',
    'user',
    'system',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('dr_entity_test');
    $this->installEntitySchema('dr_entity_test_rev');
    $this->installEntitySchema('dr_entity_test_single');
    // Needed for uninstall tests.
    $this->installSchema('user', ['users_data']);
  }

  /**
   * Tests date recur entity.
   */
  public function testDrEntityTest(): void {
    $entity = DrEntityTestBasic::create();
    $entity->dr->setValue([
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;COUNT=3',
      'infinite' => '0',
      'timezone' => 'Australia/Sydney',
    ]);
    $entity->save();

    $tableName = 'date_recur__dr_entity_test__dr';
    $actualCount = $this->container->get('database')
      ->select($tableName)
      ->countQuery()
      ->execute()
      ->fetchField();
    static::assertEquals(3, $actualCount);
  }

  /**
   * Tests occurrences table is dropped when date recur entity is uninstalled.
   *
   * @covers \Drupal\date_recur\DateRecurOccurrences::fieldStorageDelete
   */
  public function testOccurrenceTableDrop(): void {
    $this->container->get('module_installer')
      ->uninstall(['date_recur_entity_test']);

    $tableName = 'date_recur__dr_entity_test__dr';
    $actualExists = $this->container->get('database')
      ->schema()
      ->tableExists($tableName);
    static::assertFalse($actualExists);
  }

}
