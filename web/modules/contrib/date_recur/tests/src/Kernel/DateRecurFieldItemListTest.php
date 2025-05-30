<?php

declare(strict_types=1);

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\date_recur\DateRange;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\date_recur_entity_test\Entity\DrEntityTestBasic;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests date_recur field lists.
 *
 * Tests the default occurrence property definition.
 *
 * @group date_recur
 * @covers \Drupal\date_recur\Plugin\Field\DateRecurOccurrencesComputed
 * @covers \DateRecurRlOccurrenceHandler::occurrencePropertyDefinition
 * @coversDefaultClass \Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList
 */
final class DateRecurFieldItemListTest extends KernelTestBase {

  protected static $modules = [
    'date_recur_entity_test',
    'entity_test',
    'datetime',
    'datetime_range',
    'date_recur',
    'field',
    'user',
  ];

  /**
   * Entity for testing.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  private EntityTest $entity;

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test');

    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'foo',
      'type' => 'date_recur',
      'settings' => [
        'datetime_type' => DateRecurItem::DATETIME_TYPE_DATETIME,
      ],
    ]);
    $field_storage->save();

    $field = [
      'field_name' => 'foo',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ];
    FieldConfig::create($field)->save();

    // @todo convert to base field, attach field not required to test.
    $this->entity = EntityTest::create();
  }

  /**
   * Tests list.
   */
  public function testList(): void {
    $this->entity->foo = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
      'infinite' => '1',
      'timezone' => 'Australia/Sydney',
    ];

    static::assertTrue($this->entity->foo->occurrences instanceof \Generator);
    // Iterate over it a bit, because this is an infinite RRULE it will go
    // forever.
    $iterationCount = 0;
    $maxIterations = 7;
    foreach ($this->entity->foo->occurrences as $occurrence) {
      static::assertTrue($occurrence instanceof DateRange);
      $iterationCount++;
      if ($iterationCount >= $maxIterations) {
        break;
      }
    }
    static::assertEquals($maxIterations, $iterationCount);
  }

  /**
   * Tests default values are available programmatically.
   */
  public function testDefaultValues(): void {
    $this->installEntitySchema('dr_entity_test');

    $defaultRrule = 'FREQ=WEEKLY;COUNT=995';

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager */
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $baseFields = $entityFieldManager->getBaseFieldDefinitions('dr_entity_test');
    $baseFieldOverride = BaseFieldOverride::createFromBaseFieldDefinition($baseFields['dr'], 'dr_entity_test');
    $baseFieldOverride->setDefaultValue([['default_rrule' => $defaultRrule]]);
    $baseFieldOverride->save();

    $entity = DrEntityTestBasic::create();
    static::assertEquals($defaultRrule, $entity->dr->first()->rrule);
  }

  /**
   * Tests cached helper instance on items are reset if values is modified.
   *
   * @covers ::onChange
   */
  public function testHelperResetAfterItemOverwritten(): void {
    $entity = DrEntityTestBasic::create();
    $entity->dr->setValue([
      [
        'value' => '2014-06-15T23:00:01',
        'end_value' => '2014-06-16T07:00:02',
        'timezone' => 'Indian/Christmas',
        'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;COUNT=5',
      ],
    ]);

    /** @var \Drupal\date_recur\DateRecurHelperInterface $helper1 */
    $helper1 = $entity->dr->first()?->getHelper();
    $firstOccurrence = $helper1->getOccurrences(NULL, NULL, 1)[0];
    static::assertEquals('Mon, 16 Jun 2014 06:00:01 +0700', $firstOccurrence->getStart()->format('r'));
    static::assertEquals('Mon, 16 Jun 2014 14:00:02 +0700', $firstOccurrence->getEnd()->format('r'));
    static::assertEquals('WEEKLY', $helper1->getRules()[0]->getFrequency());

    // Overwrite item.
    $entity->dr[0] = [
      'value' => '2015-07-15T23:00:03',
      'end_value' => '2015-07-16T07:00:04',
      'timezone' => 'Indian/Christmas',
      'rrule' => 'FREQ=DAILY;COUNT=3',
    ];

    /** @var \Drupal\date_recur\DateRecurHelperInterface $helper2 */
    $helper2 = $entity->dr->first()?->getHelper();
    $firstOccurrence = $helper2->getOccurrences(NULL, NULL, 1)[0];
    static::assertEquals('Thu, 16 Jul 2015 06:00:03 +0700', $firstOccurrence->getStart()->format('r'));
    static::assertEquals('Thu, 16 Jul 2015 14:00:04 +0700', $firstOccurrence->getEnd()->format('r'));
    static::assertEquals('DAILY', $helper2->getRules()[0]->getFrequency());
  }

}
