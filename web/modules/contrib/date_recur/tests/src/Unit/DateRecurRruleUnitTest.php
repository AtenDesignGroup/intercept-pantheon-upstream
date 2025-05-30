<?php

declare(strict_types=1);

namespace Drupal\Tests\date_recur\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\date_recur\DateRange;
use Drupal\date_recur\DateRecurHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Date recur tests.
 *
 * @coversDefaultClass \Drupal\date_recur\DateRecurHelper
 * @group date_recur
 */
final class DateRecurRruleUnitTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    // DrupalDateTime wants to access the language manager.
    $languageManager = $this->getMockBuilder(LanguageManagerInterface::class)
      ->getMock()
      ->expects($this::any())
      ->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));

    $container = new ContainerBuilder();
    $container->set('language_manager', $languageManager);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Test timezone.
   *
   * @param \DateTimeZone $tz
   *   A timezone for testing.
   *
   * @dataProvider providerTimezone
   */
  public function testTz(\DateTimeZone $tz): void {
    $start = new \DateTime('11pm 7 June 2005', $tz);
    $rule = $this->newRule('FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;INTERVAL=1', $start);

    // Test new method.
    $results = $rule->getOccurrences(
      NULL,
      NULL,
      1,
    );
    static::assertInstanceOf(\DateTimeInterface::class, $results[0]->getStart());
    static::assertTrue($start == $results[0]->getStart());
  }

  /**
   * Data provider for ::testTz.
   *
   * @phpstan-return \Generator<\DateTimeZone>
   */
  public static function providerTimezone(): \Generator {
    yield [new \DateTimeZone('America/Los_Angeles')];
    yield [new \DateTimeZone('UTC')];
    yield [new \DateTimeZone('Australia/Sydney')];
  }

  /**
   * Tests list.
   *
   * @covers ::generateOccurrences
   */
  public function testGenerateOccurrences(): void {
    $tz = new \DateTimeZone('Africa/Cairo');
    $start = new \DateTime('11pm 7 June 2005', $tz);
    $end = clone $start;
    $end->modify('+2 hours');
    $rule = $this->newRule('FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR', $start, $end);

    $generator = $rule->generateOccurrences();
    static::assertTrue($generator instanceof \Generator);

    $assertOccurrences = [
      [
        new \DateTime('11pm 7 June 2005', $tz),
        new \DateTime('1am 8 June 2005', $tz),
      ],
      [
        new \DateTime('11pm 8 June 2005', $tz),
        new \DateTime('1am 9 June 2005', $tz),
      ],
      [
        new \DateTime('11pm 9 June 2005', $tz),
        new \DateTime('1am 10 June 2005', $tz),
      ],
      [
        new \DateTime('11pm 10 June 2005', $tz),
        new \DateTime('1am 11 June 2005', $tz),
      ],
      [
        new \DateTime('11pm 13 June 2005', $tz),
        new \DateTime('1am 14 June 2005', $tz),
      ],
      [
        new \DateTime('11pm 14 June 2005', $tz),
        new \DateTime('1am 15 June 2005', $tz),
      ],
      [
        new \DateTime('11pm 15 June 2005', $tz),
        new \DateTime('1am 16 June 2005', $tz),
      ],
    ];

    // Iterate over it a bit, because this is an infinite RRULE it will go
    // forever.
    $iterationCount = 0;
    $maxIterations = \count($assertOccurrences);
    foreach ($generator as $occurrence) {
      static::assertTrue($occurrence instanceof DateRange);

      [$assertStart, $assertEnd] = $assertOccurrences[$iterationCount];
      static::assertTrue($assertStart == $occurrence->getStart());
      static::assertTrue($assertEnd == $occurrence->getEnd());

      $iterationCount++;
      if ($iterationCount >= $maxIterations) {
        break;
      }
    }
    static::assertEquals($maxIterations, $iterationCount);
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
    $helper = $this->newRule($string, $dtStart);
    $excluded = $helper->getExcluded();
    static::assertCount(1, $excluded);
    $expectedDate = new \DateTime('9am 6 September 2018', $tz);
    static::assertEquals($expectedDate, $excluded[0]);
  }

  /**
   * Constructs a new DateRecurHelper object.
   *
   * @param string $rrule
   *   The repeat rule.
   * @param \DateTime $startDate
   *   The initial occurrence start date.
   * @param \DateTime|null $startDateEnd
   *   The initial occurrence end date, or NULL to use start date.
   *
   * @return \Drupal\date_recur\DateRecurHelper
   *   A new DateRecurHelper object.
   */
  protected function newRule($rrule, \DateTime $startDate, ?\DateTime $startDateEnd = NULL): DateRecurHelper {
    return DateRecurHelper::create($rrule, $startDate, $startDateEnd);
  }

}
