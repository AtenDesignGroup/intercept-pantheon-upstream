<?php

declare(strict_types=1);

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\Core\Field\FieldConfigInterface;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests field validation failures as a result part grids.
 *
 * @group date_recur
 */
final class DateRecurPartGridTest extends KernelTestBase {

  protected static $modules = [
    'entity_test',
    'datetime',
    'datetime_range',
    'date_recur',
    'field',
    'user',
  ];

  /**
   * A field config for testing.
   *
   * @var \Drupal\Core\Field\FieldConfigInterface
   */
  private FieldConfigInterface $fieldConfig;

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
    $this->fieldConfig = FieldConfig::create($field);
  }

  /**
   * Tests when nothing is allowed.
   */
  public function testAllowedAll(): void {
    $this->setPartSettings([
      'all' => TRUE,
      'frequencies' => [
        // Nothing is allowed here.
        'SECONDLY' => [],
        'MINUTELY' => [],
        'HOURLY' => [],
        'DAILY' => [],
        'WEEKLY' => [],
        'MONTHLY' => [],
        'YEARLY' => [],
      ],
    ]);

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
    static::assertEquals(0, $violations->count());
  }

  /**
   * Tests when nothing is allowed.
   */
  public function testAllowedNothing(): void {
    $this->setPartSettings([
      'all' => FALSE,
      'frequencies' => [
        // Nothing is allowed.
        'SECONDLY' => [],
        'MINUTELY' => [],
        'HOURLY' => [],
        'DAILY' => [],
        'WEEKLY' => [],
        'MONTHLY' => [],
        'YEARLY' => [],
      ],
    ]);

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
    $message = \strip_tags((string) $violation->getMessage());
    static::assertEquals('Weekly is not a permitted frequency.', $message);
  }

  /**
   * Tests when a frequency is allowed or disallowed.
   */
  public function testFrequency(): void {
    $this->setPartSettings([
      'all' => FALSE,
      'frequencies' => [
        'WEEKLY' => ['*'],
      ],
    ]);

    $entity = EntityTest::create();
    $entity->foo = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => 'FREQ=DAILY;BYDAY=MO,TU,WE,TH,FR;COUNT=3',
      'infinite' => '0',
      'timezone' => 'Australia/Sydney',
    ];

    // Try a disallowed frequency.
    /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $violations */
    $violations = $entity->foo->validate();
    static::assertEquals(1, $violations->count());

    $violation = $violations->get(0);
    $message = \strip_tags((string) $violation->getMessage());
    static::assertEquals('Daily is not a permitted frequency.', $message);

    // Try an allowed frequency.
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
    static::assertEquals(0, $violations->count());
  }

  /**
   * Tests when some parts for a frequency is allowed.
   */
  public function testAllowedSomeParts(): void {
    $this->setPartSettings([
      'all' => FALSE,
      'frequencies' => [
        'WEEKLY' => ['DTSTART', 'FREQ', 'COUNT', 'INTERVAL', 'WKST'],
      ],
    ]);

    $entity = EntityTest::create();
    $entity->foo = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      // Include a disallowed part.
      'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;COUNT=3',
      'infinite' => '0',
      'timezone' => 'Australia/Sydney',
    ];

    /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $violations */
    $violations = $entity->foo->validate();
    static::assertEquals(1, $violations->count());

    $violation = $violations->get(0);
    $message = \strip_tags((string) $violation->getMessage());
    static::assertEquals('By-day is not a permitted part.', $message);

    $entity = EntityTest::create();
    $entity->foo = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      // Remove the disallowed BYDAY part.
      'rrule' => 'FREQ=WEEKLY;COUNT=3',
      'infinite' => '0',
      'timezone' => 'Australia/Sydney',
    ];

    /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $violations */
    $violations = $entity->foo->validate();
    static::assertEquals(0, $violations->count());
  }

  /**
   * Sets parts settings then saves the field config.
   *
   * @param array $settings
   *   An array of parts settings.
   */
  protected function setPartSettings(array $settings): void {
    $this->fieldConfig->setSetting('parts', $settings)->save();
  }

}
