<?php

declare(strict_types=1);

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests occurrence tables values.
 *
 * Tests with an attached field.
 *
 * @group date_recur
 */
final class DateRecurOccurrenceTableAttachedTest extends DateRecurOccurrenceTableTest {

  protected function setUp(): void {
    parent::setUp();

    $this->testEntityType = 'entity_test_rev';
    $this->installEntitySchema($this->testEntityType);

    $fieldStorage = FieldStorageConfig::create([
      'entity_type' => $this->testEntityType,
      'field_name' => 'abc',
      'type' => 'date_recur',
      'settings' => [
        'datetime_type' => DateRecurItem::DATETIME_TYPE_DATETIME,
      ],
    ]);
    $fieldStorage->save();
    $this->fieldDefinition = $fieldStorage;

    $this->fieldName = 'abc';

    $fieldConfig = FieldConfig::create([
      'field_name' => 'abc',
      'entity_type' => $this->testEntityType,
      'bundle' => $this->testEntityType,
      'settings' => [],
    ]);
    $fieldConfig->save();
  }

  /**
   * Ensure occurrence table is created and deleted for field storage entities.
   */
  public function testTableCreateDeleteOnFieldStorageCreate(): void {
    $tableName = 'date_recur__entity_test_rev__abc123';

    $actualExists = $this->container->get('database')
      ->schema()
      ->tableExists($tableName);
    static::assertFalse($actualExists);

    $fieldStorage = FieldStorageConfig::create([
      'entity_type' => $this->testEntityType,
      'field_name' => 'abc123',
      'type' => 'date_recur',
      'settings' => [
        'datetime_type' => DateRecurItem::DATETIME_TYPE_DATETIME,
      ],
    ]);
    $fieldStorage->save();

    $actualExists = $this->container->get('database')
      ->schema()
      ->tableExists($tableName);
    static::assertTrue($actualExists);

    $fieldStorage->delete();

    $actualExists = $this->container->get('database')
      ->schema()
      ->tableExists($tableName);
    static::assertFalse($actualExists);
  }

  protected function createEntity(): EntityTestRev {
    return EntityTestRev::create();
  }

}
