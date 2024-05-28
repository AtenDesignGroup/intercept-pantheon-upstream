<?php

declare(strict_types = 1);

namespace Drupal\Tests\date_recur\Unit;

use Drupal\date_recur\DateRecurPartGrid;
use Drupal\date_recur\Exception\DateRecurRulePartIncompatible;
use Drupal\Tests\UnitTestCase;

/**
 * Tests part grid class.
 *
 * @coversDefaultClass \Drupal\date_recur\DateRecurPartGrid
 * @group date_recur
 */
class DateRecurPartGridUnitTest extends UnitTestCase {

  /**
   * Tests a part grid object without making changes to it.
   *
   * @covers ::isAllowEverything
   */
  public function testOriginal(): void {
    $partGrid = $this->createPartGrid();
    static::assertTrue($partGrid->isAllowEverything());
    // Test a random frequency.
    static::assertTrue($partGrid->isFrequencyAllowed('WEEKLY'));
    // Test a random frequency and part.
    static::assertTrue($partGrid->isPartAllowed('DAILY', 'BYMONTH'));
  }

  /**
   * Tests default value.
   *
   * @covers ::isRecurringAllowed
   */
  public function testIsRecurringNotAllowedDefault(): void {
    // A created part grid without any passed parameters allows everything.
    $partGrid = $this->createPartGrid();
    static::assertTrue($partGrid->isRecurringAllowed());
  }

  /**
   * Tests recurring not allowed.
   *
   * @covers ::isRecurringAllowed
   */
  public function testIsRecurringNotAllowed(): void {
    $partGrid = $this->createPartGrid();
    $partGrid->allowParts('DAILY', []);
    static::assertFalse($partGrid->isRecurringAllowed());
  }

  /**
   * Tests recurring not allowed.
   *
   * @covers ::isRecurringAllowed
   */
  public function testIsRecurringAllowed(): void {
    $partGrid = $this->createPartGrid();
    $partGrid->allowParts('DAILY', ['BYSETPOS']);
    static::assertTrue($partGrid->isRecurringAllowed());
  }

  /**
   * Tests a part grid object without making changes to it.
   *
   * @covers ::isPartAllowed
   */
  public function testAllowParts(): void {
    $partGrid = $this->createPartGrid();
    $partGrid->allowParts('DAILY', ['BYSETPOS']);

    static::assertFalse($partGrid->isAllowEverything());

    // Test frequencies.
    static::assertTrue($partGrid->isFrequencyAllowed('DAILY'));
    static::assertFalse($partGrid->isFrequencyAllowed('WEEKLY'));

    // Test frequencies and parts.
    static::assertTrue($partGrid->isPartAllowed('DAILY', 'BYSETPOS'));
    static::assertFalse($partGrid->isPartAllowed('DAILY', 'BYMONTH'));
  }

  /**
   * Tests config settings to grid helper.
   *
   * @covers ::configSettingsToGrid
   */
  public function testSettingsToGridOriginal(): void {
    $parts = [];

    $partGrid = DateRecurPartGrid::configSettingsToGrid($parts);
    static::assertTrue($partGrid->isAllowEverything());
  }

  /**
   * Tests config settings to grid helper.
   *
   * @covers ::configSettingsToGrid
   */
  public function testSettingsToGridAllowEverything(): void {
    $parts = ['all' => TRUE];
    $partGrid = DateRecurPartGrid::configSettingsToGrid($parts);
    static::assertTrue($partGrid->isAllowEverything());

    // A false 'all' config doesn't disallow everything, it defers part
    // allowance to 'frequency' config.
    $parts = ['all' => FALSE];
    $partGrid = DateRecurPartGrid::configSettingsToGrid($parts);
    static::assertTrue($partGrid->isAllowEverything());
  }

  /**
   * Tests config settings to grid helper.
   *
   * @covers ::configSettingsToGrid
   */
  public function testSettingsToGridAllFrequenciesDisabled(): void {
    $parts = [
      'all' => FALSE,
      'frequencies' => [
        'WEEKLY' => [],
      ],
    ];

    $partGrid = DateRecurPartGrid::configSettingsToGrid($parts);
    // Test defined frequency.
    static::assertFalse($partGrid->isFrequencyAllowed('WEEKLY'));
    // Test undefined frequency.
    static::assertFalse($partGrid->isFrequencyAllowed('DAILY'));
  }

  /**
   * Tests config settings to grid helper.
   *
   * @covers ::configSettingsToGrid
   */
  public function testSettingsToGridAllPartsForFrequencyAllowed(): void {
    $parts = [
      'all' => FALSE,
      'frequencies' => [
        'WEEKLY' => [],
        'MONTHLY' => ['*', 'BYSETPOS'],
      ],
    ];

    $partGrid = DateRecurPartGrid::configSettingsToGrid($parts);
    // Test defined frequency no parts.
    static::assertFalse($partGrid->isFrequencyAllowed('WEEKLY'));
    static::assertFalse($partGrid->isPartAllowed('WEEKLY', 'BYSETPOS'));
    // Test undefined frequency.
    static::assertFalse($partGrid->isFrequencyAllowed('DAILY'));
    static::assertFalse($partGrid->isPartAllowed('DAILY', 'BYSETPOS'));
    // Test defined frequency.
    static::assertTrue($partGrid->isFrequencyAllowed('MONTHLY'));
    // Test defined part.
    static::assertTrue($partGrid->isPartAllowed('MONTHLY', 'BYSETPOS'));
    // Test undefined frequency.
    static::assertTrue($partGrid->isPartAllowed('MONTHLY', 'BYMONTH'));
  }

  /**
   * Tests config settings to grid helper with a part incompatible with a freq.
   *
   * @covers ::configSettingsToGrid
   */
  public function testIncompatiblePartException(): void {
    $partGrid = $this->createPartGrid();
    $partGrid->allowParts('DAILY', ['*']);
    // BYWEEKNO is incompatible with daily.
    $this->expectException(DateRecurRulePartIncompatible::class);
    $partGrid->isPartAllowed('DAILY', 'BYWEEKNO');
  }

  /**
   * Create a new part grid.
   *
   * @return \Drupal\date_recur\DateRecurPartGrid
   *   New part grid object.
   */
  protected function createPartGrid(): DateRecurPartGrid {
    return new DateRecurPartGrid();
  }

}
