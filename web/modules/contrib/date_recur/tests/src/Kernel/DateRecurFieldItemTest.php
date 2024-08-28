<?php

declare(strict_types=1);

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\date_recur\Exception\DateRecurHelperArgumentException;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\date_recur_entity_test\Entity\DrEntityTest;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests date_recur field.
 *
 * @group date_recur
 * @coversDefaultClass \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem
 */
class DateRecurFieldItemTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('dr_entity_test');
  }

  /**
   * Tests infinite flag is set if an infinite RRULE is set.
   */
  public function testInfinite(): void {
    $entity = DrEntityTest::create();
    $entity->dr = [
      [
        'value' => '2008-06-16T00:00:00',
        'end_value' => '2008-06-16T06:00:00',
        'rrule' => 'FREQ=DAILY',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity->save();
    static::assertTrue($entity->dr[0]->infinite === TRUE);
  }

  /**
   * Tests infinite flag is set if an non-infinite RRULE is set.
   */
  public function testNonInfinite(): void {
    $entity = DrEntityTest::create();
    $entity->dr = [
      [
        'value' => '2008-06-16T00:00:00',
        'end_value' => '2008-06-16T06:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=100',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity->save();
    static::assertTrue($entity->dr[0]->infinite === FALSE);
  }

  /**
   * Tests no violations when time zone is recognized by PHP.
   */
  public function testTimeZoneConstraintValid(): void {
    $entity = DrEntityTest::create();
    $entity->dr = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;COUNT=3',
      'infinite' => '0',
      'timezone' => 'Australia/Sydney',
    ];

    /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $violations */
    $violations = $entity->dr->validate();
    static::assertEquals(0, $violations->count());
  }

  /**
   * Tests violations when time zone is not a recognized by PHP.
   */
  public function testTimeZoneConstraintInvalidZone(): void {
    $entity = DrEntityTest::create();
    $entity->dr = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;COUNT=3',
      'infinite' => '0',
      'timezone' => 'Mars/Mariner',
    ];

    /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $violations */
    $violations = $entity->dr->validate();
    static::assertEquals(1, $violations->count());

    $violation = $violations->get(0);
    $message = (string) $violation->getMessage();
    static::assertEquals('<em class="placeholder">Mars/Mariner</em> is not a valid time zone.', $message);
  }

  /**
   * Tests violations when time zone is not a string.
   */
  public function testTimeZoneConstraintInvalidFormat(): void {
    $entity = DrEntityTest::create();
    $entity->dr = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;COUNT=3',
      'infinite' => '0',
      'timezone' => new \stdClass(),
    ];

    /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $violations */
    $violations = $entity->dr->validate();
    static::assertGreaterThanOrEqual(1, $violations->count());

    $expectedMessage = 'This value should be of the correct primitive type.';
    $list = [];
    foreach ($violations as $violation) {
      if ((string) $violation->getMessage() === $expectedMessage) {
        $list[] = $violation;
      }
    }
    static::assertCount(1, $list);
  }

  /**
   * Tests violations when RRULE over max length.
   */
  public function testRruleMaxLengthConstraint(): void {
    $this->installEntitySchema('entity_test');

    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'foo',
      'type' => 'date_recur',
      'settings' => [
        'datetime_type' => DateRecurItem::DATETIME_TYPE_DATETIME,
        // Test a super short length.
        'rrule_max_length' => 20,
      ],
    ]);
    $field_storage->save();

    $field = [
      'field_name' => 'foo',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ];
    FieldConfig::create($field)->save();

    $entity = EntityTest::create();
    $entity->foo = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;COUNT=3',
      'infinite' => '0',
      'timezone' => 'Australia/Sydney',
    ];

    /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $violations */
    $violations = $entity->foo->validate();
    static::assertEquals(1, $violations->count());

    $violation = $violations->get(0);
    $message = strip_tags((string) $violation->getMessage());
    static::assertEquals('This value is too long. It should have 20 characters or less.', $message);
  }

  /**
   * Tests when an invalid RRULE is passed.
   */
  public function testRruleInvalidConstraint(): void {
    $entity = DrEntityTest::create();
    $entity->dr = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => $this->randomMachineName(),
      'infinite' => '0',
      'timezone' => 'Australia/Sydney',
    ];

    /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $violations */
    $violations = $entity->dr->validate();
    static::assertGreaterThanOrEqual(1, $violations->count());

    $expectedMessage = 'Invalid RRULE.';
    $list = [];
    foreach ($violations as $violation) {
      if ((string) $violation->getMessage() === $expectedMessage) {
        $list[] = $violation;
      }
    }
    static::assertCount(1, $list);
  }

  /**
   * Test exception thrown if time zone is missing when getting a item helper.
   */
  public function testTimeZoneMissing(): void {
    $entity = DrEntityTest::create();
    $entity->dr = [
      [
        'value' => '2008-06-16T00:00:00',
        'end_value' => '2008-06-16T06:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=100',
        'timezone' => '',
      ],
    ];
    $this->expectException(DateRecurHelperArgumentException::class);
    $this->expectExceptionMessage('Missing time zone');
    $entity->dr[0]->getHelper();
  }

  /**
   * Test exception thrown for invalid time zones when getting a item helper.
   */
  public function testTimeZoneInvalid(): void {
    $entity = DrEntityTest::create();
    $entity->dr = [
      [
        'value' => '2008-06-16T00:00:00',
        'end_value' => '2008-06-16T06:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=100',
        'timezone' => 'Mars/Mariner',
      ],
    ];
    $this->expectException(DateRecurHelperArgumentException::class);
    $this->expectExceptionMessage('Invalid time zone');
    $entity->dr[0]->getHelper();
  }

  /**
   * Test field item generation.
   *
   * @covers ::generateSampleValue
   */
  public function testGenerateSampleValue(): void {
    $entity = DrEntityTest::create();
    $entity->dr->generateSampleItems();
    static::assertMatchesRegularExpression('/^FREQ=DAILY;COUNT=\d{1,2}$/', $entity->dr->rrule);
    static::assertFalse($entity->dr->infinite);
    static::assertTrue(in_array($entity->dr->timezone, timezone_identifiers_list(), TRUE));

    /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $violations */
    $violations = $entity->dr->validate();
    static::assertEquals(0, $violations->count());
  }

  /**
   * Tests error if time zone is empty when saving programmatically.
   *
   * Either use validate() before save and fix errors or set correct time zone.
   */
  public function testNoTimeZone(): void {
    $entity = DrEntityTest::create();
    $entity->dr = [
      [
        'value' => '2008-06-16T00:00:00',
        'end_value' => '2008-06-16T06:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=100',
      ],
    ];

    // Cannot assert message as it differs between DB engines.
    $this->expectException(\Exception::class);
    $entity->save();
  }

  /**
   * Tests error if start is empty when saving programmatically.
   *
   * Either use validate() before or use correct value.
   */
  public function testMissingStart(): void {
    $entity = DrEntityTest::create();
    $entity->dr = [
      [
        'end_value' => '2008-06-16T06:00:00',
        'timezone' => 'Pacific/Chuuk',
      ],
    ];

    // Cannot assert message as it differs between DB engines.
    $this->expectException(\Exception::class);
    $entity->save();
  }

  /**
   * Tests error if end is empty when saving programmatically.
   *
   * Either use validate() before or use correct value.
   */
  public function testMissingEnd(): void {
    $entity = DrEntityTest::create();
    $entity->dr = [
      [
        'value' => '2008-06-16T00:00:00',
        'timezone' => 'Pacific/Chuuk',
      ],
    ];

    // Cannot assert message as it differs between DB engines.
    $this->expectException(\Exception::class);
    $entity->save();
  }

  /**
   * Tests cached helper instance is reset if its dependant values are modified.
   *
   * @covers ::onChange
   */
  public function testHelperResetAfterValueChange(): void {
    $entity = DrEntityTest::create();
    $entity->dr = [
      [
        'value' => '2014-06-15T23:00:01',
        'end_value' => '2014-06-16T07:00:02',
        'timezone' => 'Indian/Christmas',
        'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;COUNT=5',
      ],
    ];

    /** @var \Drupal\date_recur\DateRecurHelperInterface $helper1 */
    $helper1 = $entity->dr[0]->getHelper();
    $firstOccurrence = $helper1->getOccurrences(NULL, NULL, 1)[0];
    static::assertEquals('Mon, 16 Jun 2014 06:00:01 +0700', $firstOccurrence->getStart()->format('r'));
    static::assertEquals('Mon, 16 Jun 2014 14:00:02 +0700', $firstOccurrence->getEnd()->format('r'));
    static::assertEquals('WEEKLY', $helper1->getRules()[0]->getFrequency());

    // Change some values.
    $entity->dr[0]->value = '2015-07-15T23:00:03';
    $entity->dr[0]->end_value = '2015-07-16T07:00:04';
    $entity->dr[0]->rrule = 'FREQ=DAILY;COUNT=3';

    /** @var \Drupal\date_recur\DateRecurHelperInterface $helper2 */
    $helper2 = $entity->dr[0]->getHelper();
    $firstOccurrence = $helper2->getOccurrences(NULL, NULL, 1)[0];
    static::assertEquals('Thu, 16 Jul 2015 06:00:03 +0700', $firstOccurrence->getStart()->format('r'));
    static::assertEquals('Thu, 16 Jul 2015 14:00:04 +0700', $firstOccurrence->getEnd()->format('r'));
    static::assertEquals('DAILY', $helper2->getRules()[0]->getFrequency());
  }

  /**
   * Tests cached helper instance on items are reset if values is overwritten.
   *
   * @covers ::setValue
   */
  public function testHelperResetAfterListOverwritten(): void {
    $entity = DrEntityTest::create();
    $entity->dr = [
      [
        'value' => '2014-06-15T23:00:01',
        'end_value' => '2014-06-16T07:00:02',
        'timezone' => 'Indian/Christmas',
        'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;COUNT=5',
      ],
    ];

    /** @var \Drupal\date_recur\DateRecurHelperInterface $helper1 */
    $helper1 = $entity->dr[0]->getHelper();
    $firstOccurrence = $helper1->getOccurrences(NULL, NULL, 1)[0];
    static::assertEquals('Mon, 16 Jun 2014 06:00:01 +0700', $firstOccurrence->getStart()->format('r'));
    static::assertEquals('Mon, 16 Jun 2014 14:00:02 +0700', $firstOccurrence->getEnd()->format('r'));
    static::assertEquals('WEEKLY', $helper1->getRules()[0]->getFrequency());

    // Change full list.
    $entity->dr = [
      [
        'value' => '2015-07-15T23:00:03',
        'end_value' => '2015-07-16T07:00:04',
        'timezone' => 'Indian/Christmas',
        'rrule' => 'FREQ=DAILY;COUNT=3',
      ],
    ];

    /** @var \Drupal\date_recur\DateRecurHelperInterface $helper2 */
    $helper2 = $entity->dr[0]->getHelper();
    $firstOccurrence = $helper2->getOccurrences(NULL, NULL, 1)[0];
    static::assertEquals('Thu, 16 Jul 2015 06:00:03 +0700', $firstOccurrence->getStart()->format('r'));
    static::assertEquals('Thu, 16 Jul 2015 14:00:04 +0700', $firstOccurrence->getEnd()->format('r'));
    static::assertEquals('DAILY', $helper2->getRules()[0]->getFrequency());
  }

  /**
   * Tests magic properties have the correct time zone.
   */
  public function testStartEndDateTimeZone(): void {
    $entity = DrEntityTest::create();
    $entity->dr = [
      [
        'value' => '2014-06-15T23:00:01',
        'end_value' => '2014-06-16T07:00:02',
        'timezone' => 'Indian/Christmas',
        'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;COUNT=5',
      ],
    ];

    /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem $item */
    $item = $entity->dr[0];
    /** @var \Drupal\Core\Datetime\DrupalDateTime $startDate */
    $startDate = $item->start_date;
    static::assertEquals('Mon, 16 Jun 2014 06:00:01 +0700', $startDate->format('r'));
    static::assertEquals('Indian/Christmas', $startDate->getTimezone()->getName());
    /** @var \Drupal\Core\Datetime\DrupalDateTime $endDate */
    $endDate = $item->end_date;
    static::assertEquals('Mon, 16 Jun 2014 14:00:02 +0700', $endDate->format('r'));
    static::assertEquals('Indian/Christmas', $endDate->getTimezone()->getName());
  }

}
