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

    // Set open dialog to current page to confirm that block settings works.
    $this->drupalGet('admin/structure/block/add/fullcalendar_block/' . $theme_name);
    $click_event = $this->assertSession()->waitForElement('css', '#edit-settings-click-event');
    $click_event->click();
    $this->submitForm([
      'settings[event_source]' => 'https://fullcalendar.io/api/demo-feeds/events.json',
      'settings[click_event][open_dialog]' => '2',
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

  /**
   * Test that malicious XSS payloads in URL parameters do not trigger an XSS.
   *
   * This test sends several malicious payloads via URL query parameters to the
   * user page. It overrides window.alert so that if any XSS payload executes,
   * the test will detect it.
   * It also confirms that the dangerous strings are not present
   * in the rendered HTML.
   */
  public function testXssQueryParameters() {
    // Define the malicious payloads.
    $malicious_start = "alert('XSS')";
    $malicious_view_mode = "<script>alert('XSS');</script>";
    $malicious_attribute = '"><img src=x onerror=alert(1)>';
    $malicious_js = "';alert('XSS');//";

    // Test with the initial payloads.
    $url = 'user/?start=' . urlencode($malicious_start) . '&viewMode=' . urlencode($malicious_view_mode);
    $this->drupalGet($url);

    // Override window.alert so that any call will set window.alertCalled.
    $this->getSession()->executeScript("
      window.alertCalled = false;
      window.alert = function(msg) {
        window.alertCalled = true;
      };
    ");

    // Wait for the Fullcalendar block to appear.
    $this->assertSession()->waitForElementVisible('css', '.fullcalendar-block', 5000);

    // Ensure that no alert was triggered.
    $alertCalled = $this->getSession()->evaluateScript('return window.alertCalled;');
    $this->assertFalse($alertCalled, 'No alert was triggered with malicious start and viewMode parameters.');

    // Confirm that the malicious view mode payload does not appear in the page.
    $pageContent = $this->getSession()->getPage()->getContent();
    $this->assertStringNotContainsString($malicious_view_mode, $pageContent, 'The malicious view mode payload is not present in the rendered HTML.');

    // Test with alternative payloads that attempt attribute
    // and JS context injection.
    $url = 'user/?start=' . urlencode($malicious_js) . '&viewMode=' . urlencode($malicious_attribute);
    $this->drupalGet($url);

    // Again, override window.alert.
    $this->getSession()->executeScript("
      window.alertCalled = false;
      window.alert = function(msg) {
        window.alertCalled = true;
      };
    ");

    // Wait for the Fullcalendar block.
    $this->assertSession()->waitForElementVisible('css', '.fullcalendar-block', 5000);

    // Check that no alert was triggered.
    $alertCalled = $this->getSession()->evaluateScript('return window.alertCalled;');
    $this->assertFalse($alertCalled, 'No alert was triggered with malicious js and attribute payloads.');

    // Ensure the malicious attribute payload is not rendered in the HTML.
    $pageContent = $this->getSession()->getPage()->getContent();
    $this->assertStringNotContainsString($malicious_attribute, $pageContent, 'The malicious attribute payload is not present in the rendered HTML.');
  }

}
