<?php

declare(strict_types=1);

namespace Drupal\Tests\date_recur\Unit;

use Drupal\date_recur\DateRecurHelperInterface;
use Drupal\date_recur\DateRecurNonRecurringHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests non-recurring implementation of helper.
 *
 * @coversDefaultClass \Drupal\date_recur\DateRecurNonRecurringHelper
 * @group date_recur
 */
final class DateRecurNonRecurringHelperUnitTest extends UnitTestCase {

  /**
   * Test occurrence generation with range limiters.
   *
   * @covers ::getOccurrences
   * @covers ::generateOccurrences
   */
  public function testOccurrence(): void {
    $helper = $this->createHelper(
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
   * Test the helper works as an iterator.
   */
  public function testIteration(): void {
    $format = 'ga j F Y';
    $start = '2am 14 April 2014';
    $end = '4am 14 April 2014';
    $helper = $this->createHelper(
      new \DateTime($start),
      new \DateTime($end),
    );
    $occurrences = [];

    // Iterate the occurrences.
    foreach ($helper as $date_range) {
      $occurrences[] = $date_range;
    }
    // There should be only one result.
    /** @var \Drupal\date_recur\DateRange $occurrence */
    $occurrence = \reset($occurrences);

    static::assertCount(1, $occurrences);
    static::assertSame($occurrence->getStart()->format($format), $start);
    static::assertSame($occurrence->getEnd()->format($format), $end);
  }

  /**
   * Creates a new helper.
   *
   * @param \DateTimeInterface[]|null[] $args
   *   Uses same arguments as
   *   \Drupal\date_recur\DateRecurNonRecurringHelper::createInstance without
   *   the RRULE arg.
   *
   * @return \Drupal\date_recur\DateRecurHelperInterface
   *   A new date recur helper instance.
   *
   * @see \Drupal\date_recur\DateRecurHelperInterface::createInstance
   */
  protected function createHelper(?\DateTimeInterface ...$args): DateRecurHelperInterface {
    return DateRecurNonRecurringHelper::createInstance('', ...$args);
  }

}
