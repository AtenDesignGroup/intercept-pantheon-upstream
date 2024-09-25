<?php

namespace Drupal\Tests\office_hours\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\office_hours\Element\OfficeHoursDatetime;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItem;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the new entity API for the office_hours field type.
 *
 * @see https://www.drupal.org/docs/automated-testing/phpunit-in-drupal
 * @see https://www.drupal.org/docs/testing/phpunit-in-drupal/running-phpunit-tests-within-phpstorm
 *
 * @group office_hours
 */
class OfficeHoursDatetimeUnitTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();

    $languageManager = $this->createMock(LanguageManagerInterface::class);
    $languageManager
      ->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));

    $container->set('language_manager', $languageManager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests using entity fields of the datetime field type.
   */
  public function testDateTimeIsEmpty(): void {

    // Test hours.
    $this::assertTrue(OfficeHoursDatetime::isEmpty(NULL), 'Test NULL is empty.');
    $this::assertTrue(OfficeHoursDatetime::isEmpty(''), 'Test empty slot is empty.');
    $this::assertTrue(OfficeHoursDatetime::isEmpty([
      'time' => '',
    ]), "Test empty 'time' value is empty.");
    $this::assertNotTrue(OfficeHoursDatetime::isEmpty([
      'time' => 'a time',
    ]), "Test not-empty 'time' value is not empty.");

    // Test slots.
    $this::assertTrue(OfficeHoursItem::isValueEmpty([
      'day' => '2',
      'starthours' => '',
      'endhours' => '',
      'comment' => '',
    ]), "Test complete array - only 'day' is set.");
    $this::assertNotTrue(OfficeHoursItem::isValueEmpty([
      'day' => '2',
      'starthours' => '',
      'endhours' => '',
      'comment' => 'There is a comment, so not empty.',
    ]), "Test complete array - only 'day' and 'comment' is set.");
    $this::assertTrue(OfficeHoursItem::isValueEmpty([
      'day' => NULL,
      'starthours' => NULL,
      'endhours' => NULL,
      'comment' => NULL,
    ]), "Test complete array with 4 NULL (from devel_generate).");
  }

  /**
   * Test 'end time' formatting.
   *
   * @dataProvider providerGetEndTimes
   */
  public function testEndTimeFormat($raw, $format, $formatted): void {
    $this->assertEquals($formatted, OfficeHoursDateHelper::format($raw, $format, TRUE));
  }

  /**
   * Helper function for 'end time' formatting.
   *
   * @return array
   *   A list of test cases.
   */
  public static function providerGetEndTimes(): array {
    return [
      "midnight1" => ['00:00', 'G', '24'],
      "midnight2" => ['00:00', 'H:i', '24:00'],
      "midnight3" => ['0:00', 'G', '24'],
      "midnight4" => ['0:00', 'H:i', '24:00'],
      "one1" => ['1:00', 'G', '1'],
      "one2" => ['01:00', 'G', '1'],
      "one3" => ['1:00', 'H:i', '01:00'],
      // "fallback" => ['0:00', 'g:i a', '12:00 am'],
    ];
  }

  /**
   * Test 'dateHelper::format' formatting.
   *
   * @see https://www.php.net/manual/en/datetime.format.php
   */
  public function testDateHelperFormat(): void {
    $format = 'Hi';
    $value = '1500';
    // 24hrs tests.
    $this->assertEquals($value, OfficeHoursDateHelper::format($raw = '15:00', $format, TRUE));
    // Ampm tests.
    $this->assertEquals($value, OfficeHoursDateHelper::format($raw = ' 3:00 P.M.', $format, TRUE));
    $this->assertEquals($value, OfficeHoursDateHelper::format($raw = '3:00 P.M.', $format, TRUE));
    $this->assertEquals($value, OfficeHoursDateHelper::format($raw = '3:00 PM', $format, TRUE));
    $this->assertEquals($value, OfficeHoursDateHelper::format($raw = '3:00pm', $format, TRUE));

    $format = 'Gi';
    $value = '300';
    // 24hrs tests.
    $this->assertEquals($value, OfficeHoursDateHelper::format($raw = ' 3:00', $format, TRUE));
    $this->assertEquals($value, OfficeHoursDateHelper::format($raw = '03:00', $format, TRUE));
    $this->assertEquals($value, OfficeHoursDateHelper::format($raw = '3:00', $format, TRUE));
    // Ampm tests.
    $this->assertEquals($value, OfficeHoursDateHelper::format($raw = ' 3:00 A.M.', $format, TRUE));
    $this->assertEquals($value, OfficeHoursDateHelper::format($raw = '3:00 A.M.', $format, TRUE));
    $this->assertEquals($value, OfficeHoursDateHelper::format($raw = '3:00 AM', $format, TRUE));
    $this->assertEquals($value, OfficeHoursDateHelper::format($raw = '3:00am', $format, TRUE));
  }

}
