<?php

declare(strict_types = 1);

namespace Drupal\Tests\date_recur\Unit;

use Drupal\date_recur\DateRecurHelperInterface;
use Drupal\date_recur\Exception\DateRecurHelperArgumentException;
use Drupal\date_recur\Rl\RlHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests Rlanvin implementation of helper.
 *
 * @coversDefaultClass \Drupal\date_recur\Rl\RlHelper
 * @group date_recur
 *
 * @ingroup RLanvinPhpRrule
 */
class DateRecurRlHelperUnitTest extends UnitTestCase {

  /**
   * Test occurrence generation with range limiters.
   *
   * @covers ::getOccurrences
   * @covers ::generateOccurrences
   */
  public function testOccurrence(): void {
    $helper = $this->createHelper(
      'FREQ=DAILY;COUNT=1',
      new \DateTime('2am 14 April 2014'),
      new \DateTime('4am 14 April 2014'),
    );

    // Test out of range (before).
    $occurrences = $helper->getOccurrences(
      new \DateTime('1am 14 April 2014'),
      new \DateTime('1:30am 14 April 2014'),
    );
    static::assertCount(0, $occurrences);

    // Test out of range (after).
    $occurrences = $helper->getOccurrences(
      new \DateTime('4:30am 14 April 2014'),
      new \DateTime('5am 14 April 2014'),
    );
    static::assertCount(0, $occurrences);

    // Test in range (intersects occurrence start).
    $occurrences = $helper->getOccurrences(
      new \DateTime('1am 14 April 2014'),
      new \DateTime('3am 14 April 2014'),
    );
    static::assertCount(1, $occurrences);

    // Test in range (exact).
    $occurrences = $helper->getOccurrences(
      new \DateTime('2am 14 April 2014'),
      new \DateTime('4am 14 April 2014'),
    );
    static::assertCount(1, $occurrences);

    // Test in range (within).
    $occurrences = $helper->getOccurrences(
      new \DateTime('2:30am 14 April 2014'),
      new \DateTime('3:30am 14 April 2014'),
    );
    static::assertCount(1, $occurrences);

    // Test in range (intersects occurrence end).
    $occurrences = $helper->getOccurrences(
      new \DateTime('3am 14 April 2014'),
      new \DateTime('5am 14 April 2014'),
    );
    static::assertCount(1, $occurrences);

    // Test in range but zero limit.
    $occurrences = $helper->getOccurrences(
      new \DateTime('1am 14 April 2014'),
      new \DateTime('3am 14 April 2014'),
      0,
    );
    static::assertCount(0, $occurrences);
  }

  /**
   * Tests invalid argument for limit.
   */
  public function testInvalidLimit(): void {
    $helper = $this->createHelper(
      'FREQ=DAILY;COUNT=10',
      new \DateTime('2am 14 April 2014'),
      new \DateTime('4am 14 April 2014'),
    );

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid count limit.');
    $helper->getOccurrences(
      new \DateTime('1am 14 April 2014'),
      new \DateTime('3am 14 April 2014'),
      -1,
    );
  }

  /**
   * Tests frequency method of rules returned by helper.
   */
  public function testFrequency(): void {
    $dtStart = new \DateTime('9am 16 June 2014');
    $rrule = 'FREQ=DAILY;COUNT=10';
    $instance = $this->createHelper($rrule, $dtStart);

    $rules = $instance->getRules();
    static::assertCount(1, $rules);
    $rule = $rules[0];
    static::assertEquals('DAILY', $rule->getFrequency());
  }

  /**
   * Tests single EXDATE value.
   */
  public function testExdate(): void {
    $timeZone = new \DateTimeZone('Asia/Singapore');
    // Difference between exdate (UTC) and Singapore is 8 hours.
    $dtStart = new \DateTime('10am 16 June 2014', $timeZone);
    $rrule = 'RRULE:FREQ=DAILY;COUNT=6
EXDATE:20140617T020000Z';
    $instance = $this->createHelper($rrule, $dtStart);

    $occurrences = $instance->getOccurrences();
    static::assertCount(5, $occurrences);
    // Occurrence time zones are same as start date.
    static::assertEquals('Mon, 16 Jun 2014 10:00:00 +0800', $occurrences[0]->getStart()->format('r'));
    static::assertEquals('Wed, 18 Jun 2014 10:00:00 +0800', $occurrences[1]->getStart()->format('r'));
    static::assertEquals('Thu, 19 Jun 2014 10:00:00 +0800', $occurrences[2]->getStart()->format('r'));
    static::assertEquals('Fri, 20 Jun 2014 10:00:00 +0800', $occurrences[3]->getStart()->format('r'));
    static::assertEquals('Sat, 21 Jun 2014 10:00:00 +0800', $occurrences[4]->getStart()->format('r'));

    // Exdate time zones are same as original, not same as start date.
    $exDates = $instance->getRlRuleset()->getExDates();
    static::assertCount(1, $exDates);
    static::assertEquals('Tue, 17 Jun 2014 02:00:00 +0000', $exDates[0]->format('r'));
  }

  /**
   * Tests multiple EXDATE values.
   */
  public function testExdateMultiple(): void {
    $timeZone = new \DateTimeZone('Asia/Singapore');
    // Difference between exdate (UTC) and Singapore is 8 hours.
    $dtStart = new \DateTime('10am 16 June 2014', $timeZone);
    $rrule = 'RRULE:FREQ=DAILY;COUNT=6
EXDATE:20140617T020000Z,20140619T020000Z';
    $instance = $this->createHelper($rrule, $dtStart);

    $occurrences = $instance->getOccurrences();
    static::assertCount(4, $occurrences);
    // Occurrence time zones are same as start date.
    static::assertEquals('Mon, 16 Jun 2014 10:00:00 +0800', $occurrences[0]->getStart()->format('r'));
    static::assertEquals('Wed, 18 Jun 2014 10:00:00 +0800', $occurrences[1]->getStart()->format('r'));
    static::assertEquals('Fri, 20 Jun 2014 10:00:00 +0800', $occurrences[2]->getStart()->format('r'));
    static::assertEquals('Sat, 21 Jun 2014 10:00:00 +0800', $occurrences[3]->getStart()->format('r'));

    // Exdate time zones are same as original, not same as start date.
    $exDates = $instance->getRlRuleset()->getExDates();
    static::assertCount(2, $exDates);
    static::assertEquals('Tue, 17 Jun 2014 02:00:00 +0000', $exDates[0]->format('r'));
    static::assertEquals('Thu, 19 Jun 2014 02:00:00 +0000', $exDates[1]->format('r'));
  }

  /**
   * Tests EXDATE is ignored because of time zone differences.
   */
  public function testExdateTimezone(): void {
    $timeZone = new \DateTimeZone('Asia/Singapore');
    // Difference between exdate (UTC) and Singapore is 8 hours.
    // Exdate will be ignored because it never happens at the same time as
    // occurrences.
    $dtStart = new \DateTime('9am 16 June 2014', $timeZone);
    $rrule = 'RRULE:FREQ=DAILY;COUNT=6
EXDATE:20140617T000000Z,20140618T000000Z';
    $instance = $this->createHelper($rrule, $dtStart);

    $occurrences = $instance->getOccurrences();
    static::assertCount(6, $occurrences);
  }

  /**
   * Tests single RDATE value.
   *
   * Rdates serve to add extra fixed time occurrences, they are combined with
   * any dates computed by RRULEs.
   */
  public function testRdate(): void {
    $timeZone = new \DateTimeZone('Asia/Singapore');
    $dtStart = new \DateTime('11am 4 Oct 2012', $timeZone);
    $rrule = 'RRULE:FREQ=WEEKLY;COUNT=3
RDATE:20121006T120000Z';
    $instance = $this->createHelper($rrule, $dtStart);

    // Tests the RDATE is found between all the RRULE occurrences, such that it
    // is chronological date order, not simply appended to the RRULE list.
    $occurrences = $instance->getOccurrences();
    static::assertCount(4, $occurrences);

    // Occurrence time zones are same as start date.
    static::assertEquals('Thu, 04 Oct 2012 11:00:00 +0800', $occurrences[0]->getStart()->format('r'));
    // The RDATE date/time zone is not normalised to the start-date time zone.
    static::assertEquals('Sat, 06 Oct 2012 12:00:00 +0000', $occurrences[1]->getStart()->format('r'));
    static::assertEquals('Thu, 11 Oct 2012 11:00:00 +0800', $occurrences[2]->getStart()->format('r'));
    static::assertEquals('Thu, 18 Oct 2012 11:00:00 +0800', $occurrences[3]->getStart()->format('r'));

    // Rdate time zones are same as original, not same as start date.
    $rDates = $instance->getRlRuleset()->getDates();
    static::assertCount(1, $rDates);
    static::assertEquals('Sat, 06 Oct 2012 12:00:00 +0000', $rDates[0]->format('r'));
  }

  /**
   * Tests multiple RDATE values.
   */
  public function testRdateMultiple(): void {
    $timeZone = new \DateTimeZone('Asia/Singapore');
    $dtStart = new \DateTime('11am 4 Oct 2012', $timeZone);
    $rrule = 'RRULE:FREQ=WEEKLY;COUNT=3
RDATE:20121006T120000Z,20121013T120000Z';
    $instance = $this->createHelper($rrule, $dtStart);

    // Tests the RDATE is found between all the RRULE occurrences, such that it
    // is chronological date order, not simply appended to the RRULE list.
    $occurrences = $instance->getOccurrences();
    static::assertCount(5, $occurrences);

    // Occurrence time zones are same as start date.
    static::assertEquals('Thu, 04 Oct 2012 11:00:00 +0800', $occurrences[0]->getStart()->format('r'));
    // The RDATE date/time zone is not normalised to the start-date time zone.
    static::assertEquals('Sat, 06 Oct 2012 12:00:00 +0000', $occurrences[1]->getStart()->format('r'));
    static::assertEquals('Thu, 11 Oct 2012 11:00:00 +0800', $occurrences[2]->getStart()->format('r'));
    static::assertEquals('Sat, 13 Oct 2012 12:00:00 +0000', $occurrences[3]->getStart()->format('r'));
    static::assertEquals('Thu, 18 Oct 2012 11:00:00 +0800', $occurrences[4]->getStart()->format('r'));

    // Rdate time zones are same as original, not same as start date.
    $rDates = $instance->getRlRuleset()->getDates();
    static::assertCount(2, $rDates);
    static::assertEquals('Sat, 06 Oct 2012 12:00:00 +0000', $rDates[0]->format('r'));
    static::assertEquals('Sat, 13 Oct 2012 12:00:00 +0000', $rDates[1]->format('r'));
  }

  /**
   * Tests parts that were not passed originally, are not returned.
   */
  public function testRedundantPartsOmitted(): void {
    $dtStart = new \DateTime('9am 16 June 2014');
    $rrule = 'FREQ=DAILY;COUNT=10';
    $instance = $this->createHelper($rrule, $dtStart);

    $rules = $instance->getRules();
    static::assertCount(1, $rules);
    $rule = $rules[0];

    $parts = $rule->getParts();
    // Rlanvin/rrule will return parts: 'DTSTART', 'FREQ', 'COUNT', 'INTERVAL',
    // 'WKST'. However we just need to test completely unrelated parts such as
    // BYMONTHDAY etc arn't returned here.
    static::assertArrayHasKey('DTSTART', $parts);
    static::assertArrayHasKey('COUNT', $parts);
    static::assertArrayNotHasKey('BYMONTHDAY', $parts);
  }

  /**
   * Tests where a multiline rule without is missing the type prefix.
   */
  public function testMultilineMissingColon(): void {
    $rrule = 'RRULE:FREQ=DAILY;BYDAY=MO,TU,WE,TH,FR;COUNT=3
EXDATE:19960402T010000Z
foobar';

    $this->expectException(DateRecurHelperArgumentException::class);
    $this->expectExceptionMessage('Multiline RRULE must be prefixed with either: RRULE, EXDATE, EXRULE, or RDATE. Missing for line 3');
    $this->createHelper($rrule, new \DateTime());
  }

  /**
   * Tests list.
   *
   * @covers ::getExcluded
   */
  public function testGetExcluded(): void {
    $tz = new \DateTimeZone('Asia/Singapore');
    $dtStart = new \DateTime('9am 4 September 2018', $tz);
    $string = 'RRULE:FREQ=DAILY;BYDAY=MO,TU,WE,TH,FR;COUNT=3
EXDATE:20180906T010000Z';
    $helper = $this->createHelper($string, $dtStart);
    $excluded = $helper->getExcluded();
    static::assertCount(1, $excluded);
    $expectedDate = new \DateTime('9am 6 September 2018', $tz);
    static::assertEquals($expectedDate, $excluded[0]);
  }

  /**
   * Creates a new helper.
   *
   * @param string|\DateTimeInterface|null|mixed $args
   *   Uses same arguments as
   *   \Drupal\date_recur\DateRecurHelperInterface::createInstance.
   *
   * @return \Drupal\date_recur\DateRecurHelperInterface
   *   A new date recur helper instance.
   *
   * @see \Drupal\date_recur\DateRecurHelperInterface::createInstance
   */
  protected function createHelper(...$args): DateRecurHelperInterface {
    return RlHelper::createInstance(...func_get_args());
  }

}
