<?php

declare(strict_types=1);

namespace Drupal\Tests\date_recur_modular\Functional;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\date_recur_entity_test\Entity\DrEntityTestBasic;

/**
 * Tests Oscar Widget.
 *
 * @group date_recur_modular_widget
 * @coversDefaultClass \Drupal\date_recur_modular\Plugin\Field\FieldWidget\DateRecurModularOscarWidget
 */
final class DateRecurModularOscarTest extends WebDriverTestBase {

  protected $defaultTheme = 'stark';

  protected static $modules = [
    'date_recur_modular',
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

    $display = \Drupal::service('entity_display.repository')->getFormDisplay('dr_entity_test', 'dr_entity_test', 'default');
    $component = $display->getComponent('dr');
    $component['region'] = 'content';
    $component['type'] = 'date_recur_modular_oscar';
    $component['settings'] = [];
    $display->setComponent('dr', $component);
    $display->save();

    $user = $this->drupalCreateUser(['administer entity_test content']);
    $user->timezone = 'Asia/Singapore';
    $user->save();
    $this->drupalLogin($user);
  }

  /**
   * Tests field widget input is converted to appropriate database values.
   *
   * @param array $values
   *   Array of form fields to submit.
   * @param array $expected
   *   Array of expected field normalized values.
   * @param bool $clickAllDay
   *   Whether to click the all day toggle.
   *
   * @dataProvider providerTestWidget
   */
  public function testWidget(array $values, array $expected, $clickAllDay = FALSE): void {
    $entity = DrEntityTestBasic::create();
    $entity->save();
    $this->drupalGet($entity->toUrl('edit-form'));

    if ($clickAllDay) {
      $this->getSession()->getPage()->find('xpath', '//label[@for="edit-dr-0-is-all-day-all-day"]')->click();
    }

    $this->submitForm($values, 'Save');
    $this->assertSession()->pageTextContains('has been updated.');

    $entity = DrEntityTestBasic::load($entity->id());
    self::assertEquals($expected, $entity->dr[0]->getValue());
  }

  /**
   * Data provider.
   */
  public static function providerTestWidget(): \Generator {
    yield 'once' => [
      [
        'dr[0][mode]' => 'once',
        'dr[0][day_start]' => '04/14/2015',
        'dr[0][times][time_start]' => '09:00:00am',
        'dr[0][times][time_end]' => '05:00:00pm',
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => NULL,
        'infinite' => FALSE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    yield 'multi' => [
      [
        'dr[0][mode]' => 'multiday',
        'dr[0][daily_count]' => 3,
        'dr[0][day_start]' => '04/14/2015',
        'dr[0][times][time_start]' => '09:00:00am',
        'dr[0][times][time_end]' => '5:00:00pm',
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=DAILY;INTERVAL=1;COUNT=3',
        'infinite' => FALSE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    yield 'weekly' => [
      [
        'dr[0][mode]' => 'weekly',
        'dr[0][weekdays][MO]' => TRUE,
        'dr[0][weekdays][WE]' => TRUE,
        'dr[0][weekdays][FR]' => TRUE,
        'dr[0][day_start]' => '04/14/2015',
        'dr[0][times][time_start]' => '09:00:00am',
        'dr[0][times][time_end]' => '05:00:00pm',
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO,WE,FR',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    yield 'fortnightly' => [
      [
        'dr[0][mode]' => 'fortnightly',
        'dr[0][weekdays][MO]' => TRUE,
        'dr[0][weekdays][WE]' => TRUE,
        'dr[0][weekdays][FR]' => TRUE,
        'dr[0][day_start]' => '04/14/2015',
        'dr[0][times][time_start]' => '09:00:00am',
        'dr[0][times][time_end]' => '05:00:00pm',
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=WEEKLY;INTERVAL=2;BYDAY=MO,WE,FR',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    yield 'allday' => [
      [
        'dr[0][mode]' => 'once',
        'dr[0][day_start]' => '04/14/2015',
      ],
      [
        'value' => '2015-04-13T16:00:00',
        'end_value' => '2015-04-14T15:59:59',
        'rrule' => NULL,
        'infinite' => FALSE,
        'timezone' => 'Asia/Singapore',
      ],
      TRUE,
    ];

    // First Friday of the month.
    yield 'monthly 1 ordinal 1 weekday' => [
      [
        'dr[0][mode]' => 'monthly',
        'dr[0][day_start]' => '04/14/2015',
        'dr[0][times][time_start]' => '09:00:00am',
        'dr[0][times][time_end]' => '05:00:00pm',
        // Set weekday first, ordinals will appear after it.
        'dr[0][weekdays][FR]' => TRUE,
        'dr[0][ordinals][1]' => TRUE,
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=FR;BYSETPOS=1',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    // First Thursday and Friday of the month.
    yield 'monthly 1 ordinal 2 weekday' => [
      [
        'dr[0][mode]' => 'monthly',
        'dr[0][day_start]' => '04/14/2015',
        'dr[0][times][time_start]' => '09:00:00am',
        'dr[0][times][time_end]' => '05:00:00pm',
        'dr[0][weekdays][TH]' => TRUE,
        'dr[0][weekdays][FR]' => TRUE,
        'dr[0][ordinals][1]' => TRUE,
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=TH,FR;BYSETPOS=1,2',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    // First and second Friday of the month.
    yield 'monthly 1,2 ordinal 1 weekday' => [
      [
        'dr[0][mode]' => 'monthly',
        'dr[0][day_start]' => '04/14/2015',
        'dr[0][times][time_start]' => '09:00:00am',
        'dr[0][times][time_end]' => '05:00:00pm',
        'dr[0][weekdays][FR]' => TRUE,
        'dr[0][ordinals][1]' => TRUE,
        'dr[0][ordinals][2]' => TRUE,
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=FR;BYSETPOS=1,2',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    // First and second Thursday and Friday of the month.
    yield 'monthly 1,2 ordinal 2 weekday' => [
      [
        'dr[0][mode]' => 'monthly',
        'dr[0][day_start]' => '04/14/2015',
        'dr[0][times][time_start]' => '09:00:00am',
        'dr[0][times][time_end]' => '05:00:00pm',
        'dr[0][weekdays][TH]' => TRUE,
        'dr[0][weekdays][FR]' => TRUE,
        'dr[0][ordinals][1]' => TRUE,
        'dr[0][ordinals][2]' => TRUE,
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=TH,FR;BYSETPOS=1,2,3,4',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    // Last Thursday of the month.
    yield 'monthly -1 ordinal 1 weekday' => [
      [
        'dr[0][mode]' => 'monthly',
        'dr[0][day_start]' => '04/14/2015',
        'dr[0][times][time_start]' => '09:00:00am',
        'dr[0][times][time_end]' => '05:00:00pm',
        'dr[0][weekdays][TH]' => TRUE,
        'dr[0][ordinals][-1]' => TRUE,
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=TH;BYSETPOS=-1',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    // Last Thursday and Friday of the month.
    yield 'monthly -1 ordinal 2 weekday' => [
      [
        'dr[0][mode]' => 'monthly',
        'dr[0][day_start]' => '04/14/2015',
        'dr[0][times][time_start]' => '09:00:00am',
        'dr[0][times][time_end]' => '05:00:00pm',
        'dr[0][weekdays][TH]' => TRUE,
        'dr[0][weekdays][FR]' => TRUE,
        'dr[0][ordinals][-1]' => TRUE,
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=TH,FR;BYSETPOS=-2,-1',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    // Second to last Thursday of the month.
    yield 'monthly -2 ordinal 1 weekday' => [
      [
        'dr[0][mode]' => 'monthly',
        'dr[0][day_start]' => '04/14/2015',
        'dr[0][times][time_start]' => '09:00:00am',
        'dr[0][times][time_end]' => '05:00:00pm',
        'dr[0][weekdays][TH]' => TRUE,
        'dr[0][ordinals][-2]' => TRUE,
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=TH;BYSETPOS=-2',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    // Second to last Thursday and Friday of the month.
    yield 'monthly -4,-3 ordinal 2 weekday' => [
      [
        'dr[0][mode]' => 'monthly',
        'dr[0][day_start]' => '04/14/2015',
        'dr[0][times][time_start]' => '09:00:00am',
        'dr[0][times][time_end]' => '05:00:00pm',
        'dr[0][weekdays][TH]' => TRUE,
        'dr[0][weekdays][FR]' => TRUE,
        'dr[0][ordinals][-2]' => TRUE,
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=TH,FR;BYSETPOS=-4,-3',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    // Last and Second to last Thursday and Friday of the month.
    yield 'monthly -4,-3-2,-1 ordinal 2 weekday' => [
      [
        'dr[0][mode]' => 'monthly',
        'dr[0][day_start]' => '04/14/2015',
        'dr[0][times][time_start]' => '09:00:00am',
        'dr[0][times][time_end]' => '05:00:00pm',
        'dr[0][weekdays][TH]' => TRUE,
        'dr[0][weekdays][FR]' => TRUE,
        'dr[0][ordinals][-1]' => TRUE,
        'dr[0][ordinals][-2]' => TRUE,
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=TH,FR;BYSETPOS=-4,-3,-2,-1',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    // Combination second and second to last Thursday and Friday of the month.
    yield 'monthly -4,-3,3,4 ordinal 2 weekday' => [
      [
        'dr[0][mode]' => 'monthly',
        'dr[0][day_start]' => '04/14/2015',
        'dr[0][times][time_start]' => '09:00:00am',
        'dr[0][times][time_end]' => '05:00:00pm',
        'dr[0][weekdays][TH]' => TRUE,
        'dr[0][weekdays][FR]' => TRUE,
        'dr[0][ordinals][2]' => TRUE,
        'dr[0][ordinals][-2]' => TRUE,
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=TH,FR;BYSETPOS=-4,-3,3,4',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];
  }

  /**
   * Tests times fields end before start.
   */
  public function testTimesEndBeforeStart(): void {
    $entity = DrEntityTestBasic::create();
    $entity->save();

    $edit = [
      'dr[0][mode]' => 'weekly',
      'dr[0][day_start]' => '04/14/2015',
      'dr[0][times][time_start]' => '09:00:00am',
      'dr[0][times][time_end]' => '08:00:00am',
      'dr[0][weekdays][MO]' => TRUE,
    ];
    $this->drupalGet($entity->toUrl('edit-form'));
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('End time must be after start time.');
  }

  /**
   * Tests times fields end same as start.
   */
  public function testTimesEndEqualStart(): void {
    $entity = DrEntityTestBasic::create();
    $entity->save();

    $edit = [
      'dr[0][mode]' => 'weekly',
      'dr[0][day_start]' => '04/14/2015',
      'dr[0][times][time_start]' => '09:00:00am',
      'dr[0][times][time_end]' => '09:00:00am',
      'dr[0][weekdays][MO]' => TRUE,
    ];
    $this->drupalGet($entity->toUrl('edit-form'));
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('has been updated.');
  }

  /**
   * Tests times fields end after start.
   */
  public function testTimesEndAfterStart(): void {
    $entity = DrEntityTestBasic::create();
    $entity->save();

    $edit = [
      'dr[0][mode]' => 'weekly',
      'dr[0][day_start]' => '04/14/2015',
      'dr[0][times][time_start]' => '09:00:00am',
      'dr[0][times][time_end]' => '10:00:00am',
      'dr[0][weekdays][MO]' => TRUE,
    ];
    $this->drupalGet($entity->toUrl('edit-form'));
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('has been updated.');
  }

  /**
   * Tests times fields end not set.
   */
  public function testTimesStartNotSet(): void {
    $entity = DrEntityTestBasic::create();
    $entity->save();

    $edit = [
      'dr[0][mode]' => 'weekly',
      'dr[0][day_start]' => '04/14/2015',
      'dr[0][times][time_end]' => '09:00:00am',
      'dr[0][weekdays][MO]' => TRUE,
    ];
    $this->drupalGet($entity->toUrl('edit-form'));
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Invalid start time.');
  }

  /**
   * Tests times fields end not set.
   */
  public function testTimesEndNotSet(): void {
    $entity = DrEntityTestBasic::create();
    $entity->save();

    $edit = [
      'dr[0][mode]' => 'weekly',
      'dr[0][day_start]' => '04/14/2015',
      'dr[0][times][time_start]' => '09:00:00am',
      'dr[0][weekdays][MO]' => TRUE,
    ];
    $this->drupalGet($entity->toUrl('edit-form'));
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Invalid end time.');
  }

  /**
   * Tests times fields end not set.
   */
  public function testTimesDayNotSet(): void {
    $entity = DrEntityTestBasic::create();
    $entity->save();

    $edit = [
      'dr[0][mode]' => 'weekly',
      'dr[0][times][time_start]' => '09:00:00am',
      'dr[0][times][time_end]' => '09:00:00am',
      'dr[0][weekdays][MO]' => TRUE,
    ];
    $this->drupalGet($entity->toUrl('edit-form'));
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Invalid start day.');
  }

  /**
   * Tests full day.
   */
  public function testFullDay(): void {
    $entity = DrEntityTestBasic::create();
    $entity->save();

    $entity->dr = [
      'value' => '2015-04-13T16:00:00',
      'end_value' => '2015-04-14T15:58:00',
      'rrule' => 'FREQ=WEEKLY;INTERVAL=1;COUNT=1',
      'timezone' => 'Asia/Singapore',
    ];
    $entity->save();

    // Ensure all day is pre-checked if day is not full.
    $this->drupalGet($entity->toUrl('edit-form'));
    $this->assertSession()->checkboxNotChecked('dr[0][is_all_day]');

    // Ensure all day is pre-checked if day is full.
    $entity->dr->end_value = '2015-04-14T15:59:00';
    $entity->save();
    $this->drupalGet($entity->toUrl('edit-form'));
    $this->assertSession()->checkboxChecked('dr[0][is_all_day]');
  }

  /**
   * Tests all-day toggle visibility.
   */
  public function testAllDayToggle(): void {
    $entity = DrEntityTestBasic::create();
    $entity->save();

    $this->drupalGet($entity->toUrl('edit-form'));

    // By default toggle is enabled, so it should be visible.
    // Assert each container exists before checking the all-day element.
    $this->assertSession()->elementExists('css', '.parts--times');
    // Must have all-day toggle.
    $this->assertSession()->elementExists('css', '.parts--times .parts--is-all-day');

    $display = \Drupal::service('entity_display.repository')->getFormDisplay('dr_entity_test', 'dr_entity_test', 'default');
    $component = $display->getComponent('dr');
    $component['settings']['all_day_toggle'] = FALSE;
    $display->setComponent('dr', $component);
    $display->save();

    $this->drupalGet($entity->toUrl('edit-form'));
    $this->assertSession()->elementExists('css', '.parts--times');
    $this->assertSession()->elementNotExists('css', '.parts--times .parts--is-all-day');
  }

}
