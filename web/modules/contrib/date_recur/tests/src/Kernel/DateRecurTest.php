<?php

declare(strict_types=1);

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\date_recur_entity_test\Entity\DrEntityTestBasic;
use Drupal\date_recur_entity_test\Entity\DrEntityTestSingleCardinality;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests basic functionality of date_recur fields.
 *
 * @group date_recur
 */
final class DateRecurTest extends KernelTestBase {

  protected static $modules = [
    'date_recur_entity_test',
    'entity_test',
    'datetime',
    'datetime_range',
    'date_recur',
    'field',
    'user',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('dr_entity_test');
  }

  /**
   * Basic tests for purposes of ensuring the entity type works.
   */
  public function testSingleCardinalityBaseField(): void {
    $this->installEntitySchema('dr_entity_test_single');

    $entity = DrEntityTestSingleCardinality::create();
    $entity->dr->setValue([
      [
        'value' => '2014-06-15T23:00:00',
        'end_value' => '2014-06-16T07:00:00',
        'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
        'infinite' => '1',
        'timezone' => 'Australia/Sydney',
      ],
      [
        'value' => '2013-06-15T23:00:00',
        'end_value' => '2013-06-16T07:00:00',
        'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
        'infinite' => '1',
        'timezone' => 'Australia/Sydney',
      ],
    ]);

    /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList $fieldList */
    $fieldList = $entity->dr;
    $validations = $fieldList->validate();
    $violation = $validations->get(0);
    $message = (string) $violation->getMessage();
    static::assertEquals('<em class="placeholder">Rule</em>: this field cannot hold more than 1 values.', $message);
    static::assertEquals(2, $fieldList->count());

    // Assert after saving and reloading entity only one value is available.
    $entity->save();
    $entity = DrEntityTestSingleCardinality::load($entity->id());
    static::assertEquals(1, $entity->dr->count());
  }

  /**
   * Tests adding a field, setting values, reading occurrences.
   */
  public function testGetOccurrences(): void {
    $this->installEntitySchema('entity_test');

    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'abc',
      'type' => 'date_recur',
      'settings' => [
        'datetime_type' => DateRecurItem::DATETIME_TYPE_DATETIME,
      ],
    ]);
    $field_storage->save();

    $field = [
      'field_name' => 'abc',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ];
    FieldConfig::create($field)->save();

    $entity = EntityTest::create();
    $entity->abc->setValue([
      [
        'value' => '2014-06-15T23:00:00',
        'end_value' => '2014-06-16T07:00:00',
        'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
        'infinite' => '1',
        'timezone' => 'Australia/Sydney',
      ],
    ]);

    // No need to save the entity.
    static::assertTrue($entity->isNew());
    /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem $item */
    $item = $entity->abc->first();
    $occurrences = $item->getHelper()
      ->getOccurrences(NULL, NULL, 2);
    static::assertEquals('Mon, 16 Jun 2014 09:00:00 +1000', $occurrences[0]->getStart()->format('r'));
    static::assertEquals('Mon, 16 Jun 2014 17:00:00 +1000', $occurrences[0]->getEnd()->format('r'));
    static::assertEquals('Tue, 17 Jun 2014 09:00:00 +1000', $occurrences[1]->getStart()->format('r'));
    static::assertEquals('Tue, 17 Jun 2014 17:00:00 +1000', $occurrences[1]->getEnd()->format('r'));
  }

  /**
   * Tests accessing occurrences with fields with no end date or rule.
   */
  public function testHelperNonRecurringWithNoEnd(): void {
    $entity = DrEntityTestBasic::create();
    $entity->dr->setValue([
      'value' => '2014-06-15T23:00:00',
      'end_value' => '',
      'rrule' => '',
      'infinite' => '0',
      'timezone' => 'Australia/Sydney',
    ]);

    // Ensure a non repeating field value generates a single occurrence.
    /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem $first */
    $first = $entity->dr->first();
    $occurrences = \iterator_to_array($first->occurrences);
    static::assertCount(1, $occurrences);

    $tz = new \DateTimeZone('Australia/Sydney');
    $startAssert = new \DateTime('9am 16 June 2014', $tz);
    static::assertEquals($startAssert, $occurrences[0]->getStart());
    static::assertEquals($startAssert, $occurrences[0]->getEnd());
  }

  /**
   * Tests accessing occurrences with fields with end date or rule.
   */
  public function testHelperNonRecurringWithEnd(): void {
    $entity = DrEntityTestBasic::create();
    $entity->dr->setValue([
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => '',
      'infinite' => '0',
      'timezone' => 'Australia/Sydney',
    ]);

    // Ensure a non repeating field value generates a single occurrence.
    /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem $first */
    $first = $entity->dr->first();
    $occurrences = \iterator_to_array($first->occurrences);
    static::assertCount(1, $occurrences);

    $tz = new \DateTimeZone('Australia/Sydney');
    $startAssert = new \DateTime('9am 16 June 2014', $tz);
    static::assertEquals($startAssert, $occurrences[0]->getStart());
    $endAssert = new \DateTime('5pm 16 June 2014', $tz);
    static::assertEquals($endAssert, $occurrences[0]->getEnd());
  }

}
