<?php

namespace Drupal\Tests\date_recur\Unit;

use Drupal\Core\Datetime\DateFormatInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\date_recur\Plugin\DateRecurInterpreter\RlInterpreter;
use Drupal\date_recur\Rl\RlDateRecurRule;
use Drupal\Tests\UnitTestCase;

/**
 * Tests Rlanvin implementation of interpreter.
 *
 * Interpretations come from the RLanvin library, test the basics here.
 *
 * @coversDefaultClass \Drupal\date_recur\Plugin\DateRecurInterpreter\RlInterpreter
 * @group date_recur
 *
 * @ingroup RLanvinPhpRrule
 */
class DateRecurRlInterpretationUnitTest extends UnitTestCase {

  /**
   * A test container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $testContainer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $dateFormat = $this->createMock(DateFormatInterface::class);
    $dateFormat->expects($this->any())
      ->method('id')
      ->willReturn('');

    $dateFormatStorage = $this->createMock(EntityStorageInterface::class);
    $dateFormatStorage->expects($this->any())
      ->method('load')
      ->with($this->anything())
      ->willReturn($dateFormat);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('date_format')
      ->willReturn($dateFormatStorage);

    $dateFormatter = $this->createMock(DateFormatterInterface::class);
    $dateFormatter->expects($this->any())
      ->method('format')
      ->with($this->anything())
      // See \Drupal\Core\Datetime\DateFormatterInterface::format.
      ->willReturnCallback(function ($timestamp, $type = 'medium', $format = '', $timezone = NULL, $langcode = NULL) {
        return (new \DateTime('@' . $timestamp))->format('r');
      });

    $container = new ContainerBuilder();
    $container->set('date.formatter', $dateFormatter);
    $container->set('entity_type.manager', $entityTypeManager);
    $this->testContainer = $container;
  }

  /**
   * Tests secondly interpretation.
   */
  public function testSecondly() {
    $parts = [
      'FREQ' => 'SECONDLY',
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Pacific/Honolulu')),
      'BYSECOND' => '59',
    ];
    $rules[] = new RlDateRecurRule($parts);
    $interpreter = RlInterpreter::create($this->testContainer, [], '', []);
    $interpretation = $interpreter->interpret($rules, 'en');
    $this->assertEquals('secondly at second 59, starting from Sun, 15 Jul 2012 14:00:00 +0000, forever', $interpretation);
  }

  /**
   * Tests minutely interpretation.
   */
  public function testMinutely() {
    $parts = [
      'FREQ' => 'MINUTELY',
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Pacific/Honolulu')),
      'BYMINUTE' => '44',
    ];
    $rules[] = new RlDateRecurRule($parts);
    $interpreter = RlInterpreter::create($this->testContainer, [], '', []);
    $interpretation = $interpreter->interpret($rules, 'en');
    $this->assertEquals('minutely at minute 44, starting from Sun, 15 Jul 2012 14:00:00 +0000, forever', $interpretation);
  }

  /**
   * Tests hourly interpretation.
   */
  public function testHourly() {
    $parts = [
      'FREQ' => 'HOURLY',
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Pacific/Honolulu')),
      'BYHOUR' => '4,7',
    ];
    $rules[] = new RlDateRecurRule($parts);
    $interpreter = RlInterpreter::create($this->testContainer, [], '', []);
    $interpretation = $interpreter->interpret($rules, 'en');
    $this->assertEquals('hourly at 4h and 7h, starting from Sun, 15 Jul 2012 14:00:00 +0000, forever', $interpretation);
  }

  /**
   * Tests daily interpretation.
   */
  public function testDaily() {
    $parts = [
      'FREQ' => 'DAILY',
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Pacific/Honolulu')),
      'BYDAY' => 'WE,SU',
    ];
    $rules[] = new RlDateRecurRule($parts);
    $interpreter = RlInterpreter::create($this->testContainer, [], '', []);
    $interpretation = $interpreter->interpret($rules, 'en');
    $this->assertEquals('daily on Wednesday and Sunday, starting from Sun, 15 Jul 2012 14:00:00 +0000, forever', $interpretation);
  }

  /**
   * Tests weekly interpretation.
   */
  public function testWeekly() {
    $parts = [
      'FREQ' => 'WEEKLY',
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Pacific/Honolulu')),
      'BYDAY' => 'MO,TU',
    ];
    $rules[] = new RlDateRecurRule($parts);
    $interpreter = RlInterpreter::create($this->testContainer, [], '', []);
    $interpretation = $interpreter->interpret($rules, 'en');
    $this->assertEquals('weekly on Monday and Tuesday, starting from Sun, 15 Jul 2012 14:00:00 +0000, forever', $interpretation);
  }

  /**
   * Tests monthly interpretation.
   */
  public function testMonthly() {
    $parts = [
      'FREQ' => 'MONTHLY',
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Pacific/Honolulu')),
      'BYMONTH' => '2,10',
    ];
    $rules[] = new RlDateRecurRule($parts);
    $interpreter = RlInterpreter::create($this->testContainer, [], '', []);
    $interpretation = $interpreter->interpret($rules, 'en');
    $this->assertEquals('monthly in February and October, starting from Sun, 15 Jul 2012 14:00:00 +0000, forever', $interpretation);
  }

  /**
   * Tests yearly interpretation.
   */
  public function testYearly() {
    $parts = [
      'FREQ' => 'YEARLY',
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Pacific/Honolulu')),
    ];
    $rules[] = new RlDateRecurRule($parts);
    $interpreter = RlInterpreter::create($this->testContainer, [], '', []);
    $interpretation = $interpreter->interpret($rules, 'en');
    $this->assertEquals('yearly, starting from Sun, 15 Jul 2012 14:00:00 +0000, forever', $interpretation);
  }

}
