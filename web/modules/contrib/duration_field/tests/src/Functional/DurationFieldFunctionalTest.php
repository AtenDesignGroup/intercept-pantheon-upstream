<?php

namespace Drupal\Tests\duration_field\Functional;

/**
 * Functional tests for the Duration Field module.
 *
 * @group duration_field
 */
class DurationFieldFunctionalTest extends DurationFieldBrowserTestBase {

  /**
   * The granularity options of the duration field.
   *
   * @var array
   */
  const DURATION_GRANULARITY = [
    'y',
    'm',
    'd',
    'h',
    'i',
    's',
  ];

  /**
   * Admin user for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The ID of the custom content type created for testing.
   *
   * @var string
   */
  protected $contentType;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['field', 'field_ui', 'duration_field', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the Human Friendly duration field formatter outputs correct data.
   */
  public function testHumanReadableFormatter() {
    $this->createDefaultSetup();
    $this->fillTextValue('#edit-title-0-value', 'Dummy Title');
    $this->fillTextValue('#edit-field-duration-0-duration-y', 1);
    $this->fillTextValue('#edit-field-duration-0-duration-m', 2);
    $this->fillTextValue('#edit-field-duration-0-duration-d', 3);
    $this->fillTextValue('#edit-field-duration-0-duration-h', 4);
    $this->fillTextValue('#edit-field-duration-0-duration-i', 5);
    $this->fillTextValue('#edit-field-duration-0-duration-s', 6);
    $this->click('input[name="op"]');
    $this->assertStatusCodeEquals(200);
    $this->assertTextExists('1 year 2 months 3 days 4 hours 5 minutes 6 seconds');

    $this->setHumanReadableOptions('short');
    $this->drupalGet('/node/1');
    $this->assertStatusCodeEquals(200);
    $this->assertTextExists('1 yr 2 mo 3 days 4 hr 5 min 6 s');

    $this->setHumanReadableOptions('full', 'hyphen');
    $this->drupalGet('/node/1');
    $this->assertStatusCodeEquals(200);
    $this->assertTextExists('1 year - 2 months - 3 days - 4 hours - 5 minutes - 6 seconds');

    $this->setHumanReadableOptions('full', 'comma');
    $this->drupalGet('/node/1');
    $this->assertStatusCodeEquals(200);
    $this->assertTextExists('1 year, 2 months, 3 days, 4 hours, 5 minutes, 6 seconds');

    $this->setHumanReadableOptions('full', 'newline');
    $this->drupalGet('/node/1');
    $this->assertStatusCodeEquals(200);
    $this->assertTextExists('1 year2 months3 days4 hours5 minutes6 seconds');
  }

  /**
   * Tests the Time Format duration field formatter outputs correct data.
   */
  public function testTimeFormatter() {
    $this->createDefaultSetup(['h', 'i', 's']);

    $this->fillTextValue('#edit-title-0-value', 'Dummy Title');
    $this->fillTextValue('#edit-field-duration-0-duration-h', 1);
    $this->fillTextValue('#edit-field-duration-0-duration-i', 2);
    $this->fillTextValue('#edit-field-duration-0-duration-s', 3);
    $this->click('input[name="op"]');
    $this->assertStatusCodeEquals(200);
    $this->assertTextExists('1 hour 2 minutes 3 seconds');
    $this->assertTextNotExists('year');

  }

  /**
   * Tests human readable date field formatter.
   */
  public function testHumanReadableDate() {

    $this->createDefaultSetup(['y', 'm', 'd']);

    $this->fillTextValue('#edit-title-0-value', 'Dummy Title');
    $this->fillTextValue('#edit-field-duration-0-duration-y', 6);
    $this->fillTextValue('#edit-field-duration-0-duration-m', 5);
    $this->fillTextValue('#edit-field-duration-0-duration-d', 4);
    $this->click('input[name="op"]');
    $this->assertStatusCodeEquals(200);
    $this->assertTextExists('6 years 5 months 4 days');
    $this->assertTextNotExists('minute');
  }

  /**
   * Tests the raw value field formatter.
   */
  public function testRawValue() {

    $this->createDefaultSetup();
    $this->setFormatter('raw');

    $this->drupalGet('node/add/test_type');
    $this->assertStatusCodeEquals(200);
    $this->assertSession()->addressMatches('/^\/node\/add\/test_type$/');
    $this->fillTextValue('#edit-title-0-value', 'Dummy Title');
    $this->fillTextValue('#edit-field-duration-0-duration-y', 1);
    $this->fillTextValue('#edit-field-duration-0-duration-m', 2);
    $this->fillTextValue('#edit-field-duration-0-duration-d', 3);
    $this->fillTextValue('#edit-field-duration-0-duration-h', 4);
    $this->fillTextValue('#edit-field-duration-0-duration-i', 5);
    $this->fillTextValue('#edit-field-duration-0-duration-s', 6);
    $this->click('input[name="op"]');
    $this->assertStatusCodeEquals(200);
    $this->assertTextExists('P1Y2M3DT4H5M6S');
  }

  /**
   * Tests full time for the Time Formatter.
   */
  public function testTimeFull() {

    $this->createDefaultSetup();
    $this->setFormatter('time');

    $this->drupalGet('node/add/test_type');
    $this->assertStatusCodeEquals(200);
    $this->assertSession()->addressMatches('/^\/node\/add\/test_type$/');
    $this->fillTextValue('#edit-title-0-value', 'Dummy Title');
    $this->fillTextValue('#edit-field-duration-0-duration-y', 1);
    $this->fillTextValue('#edit-field-duration-0-duration-m', 2);
    $this->fillTextValue('#edit-field-duration-0-duration-d', 3);
    $this->fillTextValue('#edit-field-duration-0-duration-h', 4);
    $this->fillTextValue('#edit-field-duration-0-duration-i', 5);
    $this->fillTextValue('#edit-field-duration-0-duration-s', 6);
    $this->click('input[name="op"]');
    $this->assertStatusCodeEquals(200);
    $this->assertTextExists('1/2/3 04:05:06');

    $this->drupalGet('node/add/test_type');
    $this->assertStatusCodeEquals(200);
    $this->assertSession()->addressMatches('/^\/node\/add\/test_type$/');
    $this->fillTextValue('#edit-title-0-value', 'Dummy Title');
    $this->fillTextValue('#edit-field-duration-0-duration-y', 1);
    $this->fillTextValue('#edit-field-duration-0-duration-m', 2);
    $this->fillTextValue('#edit-field-duration-0-duration-d', 3);
    $this->fillTextValue('#edit-field-duration-0-duration-h', 12);
    $this->fillTextValue('#edit-field-duration-0-duration-i', 13);
    $this->fillTextValue('#edit-field-duration-0-duration-s', 14);
    $this->click('input[name="op"]');
    $this->assertStatusCodeEquals(200);
    $this->assertTextExists('1/2/3 12:13:14');
  }

  /**
   * Tests the date part of the time formatter.
   */
  public function testTimeDate() {

    $this->createDefaultSetup(['y', 'm', 'd']);
    $this->setFormatter('time');

    $this->drupalGet('node/add/test_type');
    $this->assertStatusCodeEquals(200);
    $this->assertSession()->addressMatches('/^\/node\/add\/test_type$/');
    $this->fillTextValue('#edit-title-0-value', 'Dummy Title');
    $this->fillTextValue('#edit-field-duration-0-duration-y', 1);
    $this->fillTextValue('#edit-field-duration-0-duration-m', 2);
    $this->fillTextValue('#edit-field-duration-0-duration-d', 3);
    $this->click('input[name="op"]');
    $this->assertStatusCodeEquals(200);
    $this->assertTextExists('1/2/3');
  }

  /**
   * Tests the time part of a time.
   */
  public function testTimeTime() {

    $this->createDefaultSetup(['h', 'i', 's']);
    $this->setFormatter('time');

    $this->drupalGet('node/add/test_type');
    $this->assertStatusCodeEquals(200);
    $this->assertSession()->addressMatches('/^\/node\/add\/test_type$/');
    $this->fillTextValue('#edit-title-0-value', 'Dummy Title');
    $this->fillTextValue('#edit-field-duration-0-duration-h', 4);
    $this->fillTextValue('#edit-field-duration-0-duration-i', 5);
    $this->fillTextValue('#edit-field-duration-0-duration-s', 6);
    $this->click('input[name="op"]');
    $this->assertStatusCodeEquals(200);
    $this->assertTextExists('04:05:06');

    $this->drupalGet('node/add/test_type');
    $this->assertStatusCodeEquals(200);
    $this->assertSession()->addressMatches('/^\/node\/add\/test_type$/');
    $this->fillTextValue('#edit-title-0-value', 'Dummy Title');
    $this->fillTextValue('#edit-field-duration-0-duration-h', 10);
    $this->fillTextValue('#edit-field-duration-0-duration-i', 11);
    $this->fillTextValue('#edit-field-duration-0-duration-s', 12);
    $this->click('input[name="op"]');
    $this->assertStatusCodeEquals(200);
    $this->assertTextExists('10:11:12');
  }

  /**
   * Tests the various setups when the weeks option enabled.
   */
  public function testWeeksOption() {
    // Make sure weeks are working properly on its own.
    $this->createDefaultSetup(self::DURATION_GRANULARITY, TRUE);
    $this->fillTextValue('#edit-title-0-value', 'Dummy Title');
    $this->fillTextValue('#edit-field-duration-0-duration-y', 1);
    $this->fillTextValue('#edit-field-duration-0-duration-m', 2);
    $this->fillTextValue('#edit-field-duration-0-duration-d', 4);
    $this->fillTextValue('#edit-field-duration-0-duration-h', 5);
    $this->fillTextValue('#edit-field-duration-0-duration-i', 6);
    $this->fillTextValue('#edit-field-duration-0-duration-s', 7);
    $this->assertSession()->elementExists('css', 'input[name="field_duration[0][weeks]"]');
    $this->getSession()->getPage()->fillField('field_duration[0][weeks]', 3);

    $this->click('input[name="op"]');
    $this->assertStatusCodeEquals(200);
    $this->assertTextExists('1 year 2 months 3 weeks 4 days 5 hours 6 minutes 7 seconds');

    $this->setHumanReadableOptions('short');
    $this->drupalGet('/node/1');
    $this->assertStatusCodeEquals(200);
    $this->assertTextExists('1 yr 2 mo 3 wks 4 days 5 hr 6 min 7 s');

    $this->setHumanReadableOptions('full', 'hyphen');
    $this->drupalGet('/node/1');
    $this->assertStatusCodeEquals(200);
    $this->assertTextExists('1 year - 2 months - 3 weeks - 4 days - 5 hours - 6 minutes - 7 seconds');
  }

}
