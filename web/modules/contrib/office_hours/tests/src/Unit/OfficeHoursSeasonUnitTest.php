<?php

namespace Drupal\Tests\office_hours\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\OfficeHoursSeason;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the new entity API for the office_hours field type.
 *
 * @see https://www.drupal.org/docs/automated-testing/phpunit-in-drupal
 * @see https://www.drupal.org/docs/testing/phpunit-in-drupal/running-phpunit-tests-within-phpstorm
 *
 * @group office_hours
 */
class OfficeHoursSeasonUnitTest extends UnitTestCase {

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

    $time = $this->createMock(TimeInterface::class);
    $time
      ->method('getRequestTime')
      ->willReturn($_SERVER['REQUEST_TIME']);

    $container->set('language_manager', $languageManager);
    $container->set('datetime.time', $time);
    \Drupal::setContainer($container);
  }

  /**
   * Tests using Season.
   */
  public function testIsInRange(): void {

    $time = \Drupal::time()->getRequestTime() ?? 0;
    // $time ??= $this->container->get('datetime.time')->getRequestTime();
    // $time ??= \Drupal::service('datetime.time')->getRequestTime();
    $today = OfficeHoursDateHelper::today($time);
    $yesterday = strtotime('-1 day', $today);
    $tomorrow = strtotime('+1 day', $today);

    $season = new OfficeHoursSeason(123, 'yesterday', $yesterday, $today);
    $this::assertTrue($season->isInRange($time, $time), 'Test Season::isInRange(time, time).');
    $this::assertFalse($season->isInRange($time, 0), 'Test Season::isInRange(time, 0).');
    $this::assertTrue($season->isInRange($time, 1), 'Test Season::isInRange(time, 1).');

    $season = new OfficeHoursSeason(124, 'today', $today, $today);
    $this::assertTrue($season->isInRange($time, $time), 'Test Season::isInRange(time, time).');
    $this::assertFalse($season->isInRange($time, 0), 'Test Season::isInRange(time, 0).');
    $this::assertTrue($season->isInRange($time, 1), 'Test Season::isInRange(time, 1).');

    $season = new OfficeHoursSeason(125, 'today', $today, $tomorrow);
    $this::assertTrue($season->isInRange($time, $time), 'Test Season::isInRange(time, time).');
    $this::assertFalse($season->isInRange($time, 0), 'Test Season::isInRange(time, 0).');
    $this::assertTrue($season->isInRange($time, 1), 'Test Season::isInRange(time, 1).');
  }

}
