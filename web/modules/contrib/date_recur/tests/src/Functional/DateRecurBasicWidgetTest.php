<?php

declare(strict_types = 1);

namespace Drupal\Tests\date_recur\Functional;

use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Url;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\date_recur_entity_test\Entity\DrEntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests date recur basic widget.
 *
 * For some reason there are problems (as of Oct 2018) with filling date and
 * time fields with WebDriver. Using BTB in the mean time.
 *
 * @group date_recur
 * @coversDefaultClass \Drupal\date_recur\Plugin\Field\FieldWidget\DateRecurBasicWidget
 */
class DateRecurBasicWidgetTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'date_recur_basic_widget_test',
    'date_recur_entity_test',
    'entity_test',
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

    $display = \Drupal::service('entity_display.repository')->getFormDisplay('dr_entity_test', 'dr_entity_test', 'default');
    $component = $display->getComponent('dr');
    $component['region'] = 'content';
    $component['type'] = 'date_recur_basic_widget';
    $component['settings'] = [];
    $display->setComponent('dr', $component);
    $display->save();

    $user = $this->drupalCreateUser(['administer entity_test content']);
    $user->timezone = 'Asia/Singapore';
    $user->save();
    $this->drupalLogin($user);
  }

  /**
   * Test value from DB displays correctly.
   */
  public function testEditForm(): void {
    $entity = DrEntityTest::create();
    $rrule = 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR';
    $timeZone = 'Indian/Christmas';
    $entity->dr = [
      [
        // 10am-4pm weekdaily.
        'value' => '2008-06-15T22:00:00',
        'end_value' => '2008-06-17T06:00:00',
        'rrule' => $rrule,
        // UTC+7.
        'timezone' => $timeZone,
      ],
    ];
    $entity->save();

    $this->drupalGet($entity->toUrl('edit-form'));
    $this->assertSession()->fieldValueEquals('dr[0][value][date]', '2008-06-16');
    $this->assertSession()->fieldValueEquals('dr[0][value][time]', '05:00:00');
    $this->assertSession()->fieldValueEquals('dr[0][end_value][date]', '2008-06-17');
    $this->assertSession()->fieldValueEquals('dr[0][end_value][time]', '13:00:00');
    $this->assertSession()->fieldValueEquals('dr[0][timezone]', $timeZone);
    $this->assertSession()->fieldValueEquals('dr[0][rrule]', $rrule);
  }

  /**
   * Tests submitted values make it into database for new entities.
   */
  public function testSavedFormNew(): void {
    $rrule = 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR';
    // UTC-5.
    $timeZone = 'America/Bogota';
    $edit = [
      'dr[0][value][date]' => '2008-06-17',
      // This is the time in Bogota.
      'dr[0][value][time]' => '03:00:01',
      'dr[0][end_value][date]' => '2008-06-17',
      'dr[0][end_value][time]' => '12:00:04',
      'dr[0][timezone]' => $timeZone,
      'dr[0][rrule]' => $rrule,
    ];

    $url = Url::fromRoute('entity.dr_entity_test.add_form');
    $this->drupalGet($url);
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('has been created.');

    $entity = $this->getLastSavedDrEntityTest();
    $expected = [
      'value' => '2008-06-17T08:00:01',
      'end_value' => '2008-06-17T17:00:04',
      'rrule' => $rrule,
      'timezone' => $timeZone,
      'infinite' => TRUE,
    ];
    static::assertEquals($expected, $entity->dr[0]->toArray());
  }

  /**
   * Tests submitted values make it into database for pre-existing entities.
   */
  public function testSavedFormEdit(): void {
    $entity = DrEntityTest::create();
    $rrule = 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR';
    $timeZone = 'America/Bogota';
    $value = [
      'value' => '2008-06-17T08:00:01',
      'end_value' => '2008-06-17T17:00:04',
      'rrule' => $rrule,
      'timezone' => $timeZone,
      'infinite' => TRUE,
    ];
    $entity->dr = [$value];
    $entity->save();

    $this->drupalGet($entity->toUrl('edit-form'));
    // Submit the values as is.
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('has been updated.');

    // Reload the entity from storage.
    $entity = $this->getLastSavedDrEntityTest();
    static::assertEquals($value, $entity->dr[0]->toArray());
  }

  /**
   * Tests form field submission.
   *
   * Tests simple form success and failure without testing the saved entity.
   *
   * @param array $edit
   *   Form field values.
   * @param bool $isSuccess
   *   Whether submitting the form results in success.
   * @param string|null $errorMessage
   *   The the form submission results in failure, assert the error message.
   *
   * @dataProvider providerFields
   */
  public function testFields(array $edit, bool $isSuccess, ?string $errorMessage = NULL): void {
    $this->drupalGet(Url::fromRoute('entity.dr_entity_test.add_form'));
    $this->submitForm($edit, 'Save');

    if ($isSuccess) {
      $this->assertSession()->pageTextContains('dr_entity_test 1 has been created.');
    }
    else {
      $this->assertSession()->pageTextNotContains('dr_entity_test 1 has been created.');
      $this->assertSession()->pageTextContains($errorMessage);
    }
  }

  /**
   * Data provider for testFields.
   *
   * @return array
   *   Data for testing.
   */
  public function providerFields(): array {
    $scenarios = [];

    $scenarios['Test no failures if nothing is filled.'] = [
      [
        'dr[0][value][date]' => '',
        'dr[0][value][time]' => '',
        'dr[0][end_value][date]' => '',
        'dr[0][end_value][time]' => '',
        'dr[0][timezone]' => '',
        'dr[0][rrule]' => '',
      ],
      TRUE,
    ];

    $scenarios['Test failure when only start date field filled.'] = [
      [
        'dr[0][value][date]' => '2008-06-17',
        'dr[0][value][time]' => '',
        'dr[0][end_value][date]' => '',
        'dr[0][end_value][time]' => '',
        'dr[0][timezone]' => '',
        'dr[0][rrule]' => '',
      ],
      FALSE,
      'Missing time zone for date.',
    ];

    $scenarios['Test failure when only start time field filled.'] = [
      [
        'dr[0][value][date]' => '',
        'dr[0][value][time]' => '10:00:00',
        'dr[0][end_value][date]' => '',
        'dr[0][end_value][time]' => '',
        'dr[0][timezone]' => '',
        'dr[0][rrule]' => '',
      ],
      FALSE,
      'Missing time zone for date.',
    ];

    $scenarios['Test failure when start date and time field filled.'] = [
      [
        'dr[0][value][date]' => '2008-06-17',
        'dr[0][value][time]' => '10:00:00',
        'dr[0][end_value][date]' => '',
        'dr[0][end_value][time]' => '',
        'dr[0][timezone]' => '',
        'dr[0][rrule]' => '',
      ],
      FALSE,
      'Missing time zone for date.',
    ];

    $scenarios['Test failure when end date filled.'] = [
      [
        'dr[0][value][date]' => '',
        'dr[0][value][time]' => '',
        'dr[0][end_value][date]' => '2008-06-17',
        'dr[0][end_value][time]' => '',
        'dr[0][timezone]' => '',
        'dr[0][rrule]' => '',
      ],
      FALSE,
      'Missing time zone for date.',
    ];

    $scenarios['Test failure when end time filled.'] = [
      [
        'dr[0][value][date]' => '',
        'dr[0][value][time]' => '',
        'dr[0][end_value][date]' => '',
        'dr[0][end_value][time]' => '10:00:00',
        'dr[0][timezone]' => '',
        'dr[0][rrule]' => '',
      ],
      FALSE,
      'Missing time zone for date.',
    ];

    $scenarios['Test success when start date and time and time zone field filled.'] = [
      [
        'dr[0][value][date]' => '2008-06-17',
        'dr[0][value][time]' => '10:00:00',
        'dr[0][end_value][date]' => '',
        'dr[0][end_value][time]' => '',
        'dr[0][timezone]' => 'Australia/Sydney',
        'dr[0][rrule]' => '',
      ],
      TRUE,
    ];

    $scenarios['Test failure when end date and time and time zone field filled.'] = [
      [
        'dr[0][value][date]' => '',
        'dr[0][value][time]' => '',
        'dr[0][end_value][date]' => '2008-06-17',
        'dr[0][end_value][time]' => '10:00:00',
        'dr[0][timezone]' => 'Australia/Sydney',
        'dr[0][rrule]' => '',
      ],
      FALSE,
      'Start date must be set if end date is set.',
    ];

    $scenarios['Tests failure on invalid rule.'] = [
      [
        'dr[0][value][date]' => '2008-06-17',
        'dr[0][value][time]' => '12:00:00',
        'dr[0][end_value][date]' => '2008-06-17',
        'dr[0][end_value][time]' => '12:00:00',
        'dr[0][timezone]' => 'America/Chicago',
        'dr[0][rrule]' => $this->randomMachineName(),
      ],
      FALSE,
      'Repeat rule is formatted incorrectly.',
    ];

    // Tests validation that comes automatically from date range. Specifically,
    // assert end date comes on or after start date.
    $scenarios['Tests inherited validation: end before start'] = [
      [
        'dr[0][value][date]' => '2008-06-17',
        'dr[0][value][time]' => '03:00:00',
        'dr[0][end_value][date]' => '2008-06-15',
        'dr[0][end_value][time]' => '03:00:00',
        'dr[0][timezone]' => 'America/Chicago',
        'dr[0][rrule]' => 'FREQ=DAILY',
      ],
      FALSE,
      'end date cannot be before the start date',
    ];

    return $scenarios;
  }

  /**
   * Tests default values appear in widget.
   *
   * @dataProvider providerDefaultValues
   */
  public function testDefaultValues(array $baseFieldValue, array $assertFieldValues): void {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager */
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $baseFields = $entityFieldManager->getBaseFieldDefinitions('dr_entity_test');
    $baseFieldOverride = BaseFieldOverride::createFromBaseFieldDefinition($baseFields['dr'], 'dr_entity_test');
    // Default values need to evaluate FALSE per DateRecurItem::isEmpty
    // otherwise the values will be cleared out before display.
    $baseFieldOverride->setDefaultValue($baseFieldValue);
    $baseFieldOverride->save();

    $url = Url::fromRoute('entity.dr_entity_test.add_form');
    $this->drupalGet($url);

    foreach ($assertFieldValues as [$fieldName, $fieldValue]) {
      $this->assertSession()->fieldValueEquals($fieldName, $fieldValue);
    }
  }

  /**
   * Data provider for testFields.
   *
   * @return array
   *   Data for testing.
   */
  public function providerDefaultValues(): array {
    $scenarios = [];

    $scenarios['all values'] = [
      // 3pm/4pm Oslo (UTC+2) -> 8pm/9pm Christmas (UTC+7).
      [
        [
          'default_date_type' => 'relative',
          'default_date' => '12th April 2013 3pm',
          'default_end_date_type' => 'relative',
          'default_end_date' => '12th April 2013 4pm',
          'default_date_time_zone' => 'Europe/Oslo',
          'default_time_zone' => 'Indian/Christmas',
          'default_time_zone_source' => DateRecurFieldItemList::DEFAULT_TIME_ZONE_SOURCE_FIXED,
          'default_rrule' => 'FREQ=WEEKLY;COUNT=995',
        ],
      ],
      [
        ['dr[0][value][date]', '2013-04-12'],
        ['dr[0][value][time]', '20:00:00'],
        ['dr[0][end_value][date]', '2013-04-12'],
        ['dr[0][end_value][time]', '21:00:00'],
        ['dr[0][timezone]', 'Indian/Christmas'],
        ['dr[0][rrule]', 'FREQ=WEEKLY;COUNT=995'],
      ],
    ];

    $scenarios['only time zone'] = [
      [
        [
          'default_time_zone' => 'Indian/Christmas',
          'default_time_zone_source' => DateRecurFieldItemList::DEFAULT_TIME_ZONE_SOURCE_FIXED,
        ],
      ],
      [
        ['dr[0][value][date]', ''],
        ['dr[0][value][time]', ''],
        ['dr[0][end_value][date]', ''],
        ['dr[0][end_value][time]', ''],
        ['dr[0][timezone]', 'Indian/Christmas'],
        ['dr[0][rrule]', ''],
      ],
    ];

    $scenarios['only start'] = [
      [
        [
          'default_date_type' => 'relative',
          'default_date' => '12th April 2013 3pm',
          'default_date_time_zone' => 'Europe/Oslo',
          'default_time_zone' => 'Indian/Christmas',
          'default_time_zone_source' => DateRecurFieldItemList::DEFAULT_TIME_ZONE_SOURCE_FIXED,
        ],
      ],
      [
        ['dr[0][value][date]', '2013-04-12'],
        ['dr[0][value][time]', '20:00:00'],
        ['dr[0][end_value][date]', ''],
        ['dr[0][end_value][time]', ''],
        ['dr[0][timezone]', 'Indian/Christmas'],
        ['dr[0][rrule]', ''],
      ],
    ];

    return $scenarios;
  }

  /**
   * Tests if field is set to required, only start date is required.
   *
   * End date must never be required, value is copied over from start date.
   */
  public function testRequiredField(): void {
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
      // Set instance to required.
      'required' => TRUE,
    ];
    FieldConfig::create($field)->save();

    $display = \Drupal::service('entity_display.repository')->getFormDisplay('entity_test', 'entity_test', 'default');
    $component = $display->getComponent('foo');
    $component['region'] = 'content';
    $component['type'] = 'date_recur_basic_widget';
    $component['settings'] = [];
    $display->setComponent('foo', $component);
    $display->save();

    $edit = [
      'foo[0][value][date]' => '',
      'foo[0][value][time]' => '',
      'foo[0][end_value][date]' => '',
      'foo[0][end_value][time]' => '',
      'foo[0][timezone]' => 'America/Chicago',
      'foo[0][rrule]' => 'FREQ=DAILY',
    ];

    $this->drupalGet(Url::fromRoute('entity.entity_test.add_form'));
    $this->submitForm($edit, 'Save');

    $this->assertSession()->pageTextContains('The Start date is required.');
    $this->assertSession()->pageTextNotContains('The End date is required.');
  }

  /**
   * Tests if time zone is programmatically hidden, default value is used.
   *
   * Field default time zone will be populated behind the scenes..
   */
  public function testHiddenTimeZoneField(): void {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager */
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $baseFields = $entityFieldManager->getBaseFieldDefinitions('dr_entity_test');
    $baseFieldOverride = BaseFieldOverride::createFromBaseFieldDefinition($baseFields['dr'], 'dr_entity_test');
    $baseFieldOverride->setDefaultValue([
      [
        'default_time_zone' => 'Asia/Singapore',
        'default_time_zone_source' => DateRecurFieldItemList::DEFAULT_TIME_ZONE_SOURCE_FIXED,
      ],
    ]);
    $baseFieldOverride->save();

    \Drupal::state()->set('DATE_RECUR_BASIC_WIDGET_TEST_HIDDEN_TIMEZONE_FIELD_HOOK_FORM_ALTER', TRUE);

    $this->drupalGet(Url::fromRoute('entity.dr_entity_test.add_form'));

    // Time zone field should be hidden.
    $this->assertSession()->fieldNotExists('dr[0][timezone]');
    // Make sure something exists.
    $this->assertSession()->fieldExists('dr[0][rrule]');

    $edit = [
      // No time zone here, but the time zone is set from field defaults.
      'dr[0][value][date]' => '2008-06-17',
      'dr[0][value][time]' => '12:00:00',
      'dr[0][end_value][date]' => '2008-06-17',
      'dr[0][end_value][time]' => '12:00:00',
      'dr[0][rrule]' => 'FREQ=DAILY;COUNT=10',
    ];

    $this->submitForm($edit, 'Save');

    // The form would previously would not submit, an error was displayed.
    $this->assertSession()->pageTextContains('dr_entity_test 1 has been created.');

    $entity = $this->getLastSavedDrEntityTest();
    static::assertEquals([
      'value' => '2008-06-17T04:00:00',
      'end_value' => '2008-06-17T04:00:00',
      'rrule' => 'FREQ=DAILY;COUNT=10',
      'timezone' => 'Asia/Singapore',
      'infinite' => FALSE,
    ], $entity->dr[0]->toArray());
  }

  /**
   * Tests an error is displayed if a long RRULE is submitted.
   */
  public function testRruleMaxLengthError(): void {
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

    $display = \Drupal::service('entity_display.repository')->getFormDisplay('entity_test', 'entity_test', 'default');
    $component = $display->getComponent('foo');
    $component['region'] = 'content';
    $component['type'] = 'date_recur_basic_widget';
    $component['settings'] = [];
    $display->setComponent('foo', $component);
    $display->save();

    $edit = [
      'foo[0][value][date]' => '2008-06-17',
      'foo[0][value][time]' => '12:00:00',
      'foo[0][end_value][date]' => '2008-06-17',
      'foo[0][end_value][time]' => '12:00:00',
      'foo[0][timezone]' => 'America/Chicago',
      'foo[0][rrule]' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;COUNT=3',
    ];
    $this->drupalGet(Url::fromRoute('entity.entity_test.add_form'));
    $this->submitForm($edit, 'Save');

    $this->assertSession()->pageTextContains('This value is too long. It should have 20 characters or less.');
  }

  /**
   * Get last saved Dr Entity Test entity.
   *
   * @return \Drupal\date_recur_entity_test\Entity\DrEntityTest|null
   *   The entity or null if none exist.
   */
  protected function getLastSavedDrEntityTest(): ?DrEntityTest {
    $query = \Drupal::database()->query('SELECT MAX(id) FROM {dr_entity_test}');
    $query->execute();
    $maxId = $query->fetchField();
    return DrEntityTest::load($maxId);
  }

}
