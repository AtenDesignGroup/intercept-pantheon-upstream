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
    // Ensure that event links are visible on the page.
    $event_link = $this->assertSession()->waitForElement('css', 'a.fc-event[href]');
    $event_url = $event_link->getAttribute('href');
    // Click the event link.
    $event_link->click();
    // Check if the link open on current page.
    $this->assertSession()->addressEquals($event_url);
    $this->drupalGet('<front>');
  }

}
