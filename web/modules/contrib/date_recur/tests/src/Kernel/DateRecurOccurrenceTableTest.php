<?php

declare(strict_types=1);

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\date_recur\DateRecurOccurrences;
use Drupal\date_recur_entity_test\Entity\DrEntityTestRev;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests occurrence tables values.
 *
 * Tests with a base field.
 *
 * @group date_recur
 */
class DateRecurOccurrenceTableTest extends KernelTestBase {

  /**
   * Test entity type.
   *
   * @var string|null
   */
  protected ?string $testEntityType;

  /**
   * Name of field for testing.
   *
   * @var string|null
   */
  protected ?string $fieldName;

  /**
   * The field definition for testing.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionInterface|null
   */
  protected ?FieldStorageDefinitionInterface $fieldDefinition;

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

    $this->testEntityType = 'dr_entity_test_rev';
    $this->installEntitySchema($this->testEntityType);

    // This is the name of the base field.
    $this->fieldName = 'dr';

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $efm */
    $efm = \Drupal::service('entity_field.manager');
    $definitions = $efm->getFieldStorageDefinitions($this->testEntityType);
    $this->fieldDefinition = $definitions[$this->fieldName];
  }

  /**
   * Ensure occurrence table rows are created.
   */
  public function testTableRows(): void {
    $preCreate = 'P1Y';

    if ($this->fieldDefinition instanceof BaseFieldDefinition) {
      // Use BaseFieldOverride entity, similar to NodeType being able to
      // override some options of base fields.
      $fieldConfig = $this->fieldDefinition->getConfig($this->testEntityType);
    }
    else {
      $fieldConfig = FieldConfig::loadByName($this->testEntityType, $this->testEntityType, $this->fieldName);
    }
    $fieldConfig->setSetting('precreate', $preCreate);
    $fieldConfig->save();

    $entity = $this->createEntity();
    $entity->set($this->fieldName, [
      // The duration is 8 hours.
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
      'infinite' => '1',
      'timezone' => 'Australia/Sydney',
    ]);
    $entity->save();

    // Calculate number of weekdays between first occurrence and end of
    // pre-create interval.
    $tz = new \DateTimeZone('Australia/Sydney');
    $day = new \DateTime('9am 16th June 2014', $tz);
    $until = new \DateTime('now');
    $until
      ->add(new \DateInterval($preCreate));
    // See BYDAY above.
    $countDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
    $count = 0;
    while ($day <= $until) {
      if (\in_array($day->format('D'), $countDays, TRUE)) {
        $count++;
      }
      $day->modify('+1 day');
    }

    $tableName = DateRecurOccurrences::getOccurrenceCacheStorageTableName($this->fieldDefinition);
    $actualCount = $this->container->get('database')
      ->select($tableName)
      ->countQuery()
      ->execute()
      ->fetchField();
    // Make sure more than zero rows created.
    static::assertGreaterThan(0, $actualCount);
    static::assertEquals($count, $actualCount);
  }

  /**
   * Test table name generator.
   */
  public function testGetOccurrenceTableName(): void {
    $actual = DateRecurOccurrences::getOccurrenceCacheStorageTableName($this->fieldDefinition);
    $entityTypeId = $this->fieldDefinition->getTargetEntityTypeId();
    static::assertEquals('date_recur__' . $entityTypeId . '__' . $this->fieldName, $actual);
  }

  /**
   * Tests values of occurrence table.
   */
  public function testOccurrenceTableValues(): void {
    $columnNameValue = $this->fieldName . '_value';
    $columnNameEndValue = $this->fieldName . '_end_value';

    $entity = $this->createEntity();
    $entityTypeId = $entity->getEntityTypeId();
    $entity->set($this->fieldName, [
      [
        'value' => '2014-06-17T23:00:00',
        'end_value' => '2014-06-18T07:00:00',
        'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;COUNT=5',
        'infinite' => '0',
        'timezone' => 'Australia/Sydney',
      ],
      [
        'value' => '2015-07-17T02:00:00',
        'end_value' => '2015-07-18T10:00:00',
        'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;COUNT=2',
        'infinite' => '0',
        'timezone' => 'Indian/Cocos',
      ],
    ]);
    $entity->save();

    $tableName = 'date_recur__' . $entityTypeId . '__' . $this->fieldName;
    $fields = [
      'entity_id',
      'revision_id',
      'field_delta',
      'delta',
      $columnNameValue,
      $columnNameEndValue,
    ];
    $results = $this->container->get('database')
      ->select($tableName, 'occurrences')
      ->fields('occurrences', $fields)
      ->execute()
      ->fetchAll();
    static::assertCount(7, $results);

    $assertExpected = [
      [
        'entity_id' => $entity->id(),
        'revision_id' => $entity->getRevisionId(),
        'field_delta' => 0,
        'delta' => 0,
        $columnNameValue => '2014-06-17T23:00:00',
        $columnNameEndValue => '2014-06-18T07:00:00',
      ],
      [
        'entity_id' => $entity->id(),
        'revision_id' => $entity->getRevisionId(),
        'field_delta' => 0,
        'delta' => 1,
        $columnNameValue => '2014-06-18T23:00:00',
        $columnNameEndValue => '2014-06-19T07:00:00',
      ],
      [
        'entity_id' => $entity->id(),
        'revision_id' => $entity->getRevisionId(),
        'field_delta' => 0,
        'delta' => 2,
        $columnNameValue => '2014-06-19T23:00:00',
        $columnNameEndValue => '2014-06-20T07:00:00',
      ],
      [
        'entity_id' => $entity->id(),
        'revision_id' => $entity->getRevisionId(),
        'field_delta' => 0,
        'delta' => 3,
        $columnNameValue => '2014-06-22T23:00:00',
        $columnNameEndValue => '2014-06-23T07:00:00',
      ],
      [
        'entity_id' => $entity->id(),
        'revision_id' => $entity->getRevisionId(),
        'field_delta' => 0,
        'delta' => 4,
        $columnNameValue => '2014-06-23T23:00:00',
        $columnNameEndValue => '2014-06-24T07:00:00',
      ],
      [
        'entity_id' => $entity->id(),
        'revision_id' => $entity->getRevisionId(),
        'field_delta' => '1',
        'delta' => '0',
        $columnNameValue => '2015-07-17T02:00:00',
        $columnNameEndValue => '2015-07-18T10:00:00',
      ],
      [
        'entity_id' => $entity->id(),
        'revision_id' => $entity->getRevisionId(),
        'field_delta' => '1',
        'delta' => '1',
        $columnNameValue => '2015-07-20T02:00:00',
        $columnNameEndValue => '2015-07-21T10:00:00',
      ],
    ];

    foreach ($results as $actualIndex => $actualValues) {
      $expectedValues = $assertExpected[$actualIndex];
      $actualValues = (array) $actualValues;
      static::assertEquals($expectedValues, $actualValues);
    }
  }

  /**
   * Creates an unsaved test entity.
   */
  protected function createEntity(): EntityTestRev {
    return DrEntityTestRev::create();
  }

}
