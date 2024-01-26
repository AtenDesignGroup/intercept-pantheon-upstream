<?php

namespace Drupal\Tests\fullcalendar_block\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the Fullcalendar block.
 *
 * @group block
 */
class FullcalendarBlockTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'block', 'fullcalendar_block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Block id of the block.
   *
   * @var string
   */
  protected $blockId;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->rootUser);
    // Add a Fullcalendar block in content region.
    $theme_name = $this->config('system.theme')->get('default');

    // Set open dialog to currnet page to confirm that block settings works.
    $this->drupalGet('admin/structure/block/add/fullcalendar_block/' . $theme_name);
    $click_event = $this->assertSession()->waitForElement('css', '#edit-settings-click-event');
    $click_event->click();
    $this->submitForm([
      'settings[event_source]' => 'https://fullcalendar.io/api/demo-feeds/events.json',
      'settings[click_event][open_dialog]' => 2,
      'region' => 'content',
    ], 'Save block');
  }

  /**
   * Test to ensure that remove contextual link is present in the block.
   */
  public function testBlockContextualRemoveLinks() {
    // Go to the user profile page.
    $this->drupalGet('user/');
    // Ensure that event links are visible on the page.
    $event_link = $this->assertSession()->waitForElement('css', 'a.fc-event[href]');
    $event_url = $event_link->getAttribute('href');
    // Click the event link.
    $event_link->click();
    // Check if the link open on current page.
    $this->assertSession()->addressEquals($event_url);
    // As that link directing to an external page,
    // we need to go back to the test site to finish testing.
    $this->drupalGet('<front>');
  }

  /**
   * Test changing first day for the calendar.
   */
  public function testFirstDay() {
    // Go to the block configure page.
    $this->drupalGet("admin/structure/block/manage/{$this->defaultTheme}_fullcalendarblock");
    // Click the advanced fieldset to make it interactable.
    $click_event = $this->assertSession()->waitForElement('css', '#edit-settings-advanced');
    $click_event->click();
    // Change the first day to Monday.
    $this->submitForm([
      'settings[advanced][addition]' => 'firstDay: 1',
    ], 'Save block');
    // Go to the user profile page.
    $this->drupalGet('user/');
    // The first day for the calendar.
    $first_column = $this->assertSession()->waitForElement('css', 'th[role="columnheader"]:nth-of-type(1)');
    // Class name.
    $class = $first_column->getAttribute('class');
    // Monday class existing in the first column.
    $this->assertStringContainsString('fc-day-mon', $class);
  }

}
