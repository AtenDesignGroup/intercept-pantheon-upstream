<?php

namespace Drupal\Tests\addtocalendar\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests addtocalendar module.
 *
 * @group addtocalendar
 */
class AddToCalendarTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'addtocalendar_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test the output of the field formatter.
   */
  public function testAddToCalendarFormatter() {
    $node_storage = \Drupal::service('entity_type.manager')->getStorage('node');
    $node = $node_storage->create([
      'type' => 'page',
      'title' => 'My Event Title',
      'field_date' => '2023-01-01T12:00:00',
      'body' => 'This is the body',
      'field_addtocalendar' => TRUE,
    ]);
    $node->save();

    $this->drupalGet('node/' . $node->id());

    // We should see something like this for both:
    //
    // @code
    // <span class="addtocalendar" data-calendars="Google Calendar, Outlook" data-secure="auto">
    //   <a class="atcb-link">Add to Calendar</a>
    //   <var class="atc_event">
    //     <var class="atc_date_start">2023-01-01 23:00:00</var>
    //     <var class="atc_date_end">2023-08-08 03:26:18</var>
    //     <var class="atc_title">My Event Title</var>
    //     <var class="atc_description">This is the body</var>
    //     <var class="atc_location">The Internet</var>
    //     <var class="atc_organizer">Drush Site-Install</var>
    //     <var class="atc_organizer_email">admin@example.com</var>
    //     <var class="atc_timezone">Australia/Sydney</var>
    //     <var class="atc_privacy">public</var>
    //   </var>
    // </span>
    // @endcode
    //
    // Let's confirm stuff is there.
    //
    // Start with the addtocalendar field.
    $atc_label = $this->assertSession()->elementExists('css', 'div:contains(addtocalendar)');
    $span = $this->assertSession()->elementExists('css', 'div:contains(addtocalendar) + div span.addtocalendar');
    $this->assertSame('Google Calendar, Outlook', $span->getAttribute('data-calendars'));
    $this->assertSame('auto', $span->getAttribute('data-secure'));
    $this->assertSession()->elementTextContains('css', 'div:contains(addtocalendar) + div a.atcb-link', 'Add to Calendar');
    // Note: This start time got shifted.
    $this->assertSession()->elementTextContains('css', 'div:contains(addtocalendar) + div .atc_date_start', '2023-01-01 23:00:00');
    //@Todo: Add something better for end date. It's based on when the test runs?
    $this->assertSession()->elementExists('css', 'div:contains(addtocalendar) + div .atc_date_end');
    // Note: atc_title tests the 'title' branch in switch logic.
    $this->assertSession()->elementTextContains('css', 'div:contains(addtocalendar) + div .atc_title', 'My Event Title');
    // Note: atc_description tests a non-title, non-date node field.
    $this->assertSession()->elementTextContains('css', 'div:contains(addtocalendar) + div .atc_description', 'This is the body');
    // Note: atc_location tests a static value.
    $this->assertSession()->elementTextContains('css', 'div:contains(addtocalendar) + div .atc_location', 'The Internet');
    // Note: atc_organizer tests a site token.
    $this->assertSession()->elementTextContains('css', 'div:contains(addtocalendar) + div .atc_organizer', 'Drupal');
    // Note: atc_organizer_email tests a node token.
    $this->assertSession()->elementTextContains('css', 'div:contains(addtocalendar) + div .atc_organizer_email', 'My Event Title');
    $this->assertSession()->elementTextContains('css', 'div:contains(addtocalendar) + div .atc_timezone', 'Australia/Sydney');
    $this->assertSession()->elementTextContains('css', 'div:contains(addtocalendar) + div .atc_privacy', 'public');

    // Then the date field.
    // Start with the addtocalendar field.
    $atc_label = $this->assertSession()->elementExists('css', 'div:contains(Date)');
    $span = $this->assertSession()->elementExists('css', 'div:contains(Date) + div span.addtocalendar');
    // @Todo: Why is there a trailing comma?
    $this->assertSame('Google Calendar, Outlook, ', $span->getAttribute('data-calendars'));
    $this->assertSame('auto', $span->getAttribute('data-secure'));
    $this->assertSession()->elementTextContains('css', 'div:contains(Date) + div a.atcb-link', 'Add to Calendar');
    // Note: This start time does not get shifted.
    $this->assertSession()->elementTextContains('css', 'div:contains(Date) + div .atc_date_start', '2023-01-01 12:00:00');
    // Note: The end date matches the start date.
    $this->assertSession()->elementTextContains('css', 'div:contains(Date) + div .atc_date_start', '2023-01-01 12:00:00');
    // Note: atc_title tests the 'title' branch in switch logic.
    $this->assertSession()->elementTextContains('css', 'div:contains(Date) + div .atc_title', 'My Event Title');
    // Note: atc_description tests a non-title, non-date node field.
    $this->assertSession()->elementTextContains('css', 'div:contains(Date) + div .atc_description', 'This is the body');
    // Note: atc_location tests a static value.
    $this->assertSession()->elementTextContains('css', 'div:contains(Date) + div .atc_location', 'The Internet');
    // Note: atc_organizer tests a site token.
    $this->assertSession()->elementTextContains('css', 'div:contains(Date) + div .atc_organizer', 'Drupal');
    // Note: atc_organizer_email tests a node token.
    $this->assertSession()->elementTextContains('css', 'div:contains(Date) + div .atc_organizer_email', 'My Event Title');
    $this->assertSession()->elementTextContains('css', 'div:contains(Date) + div .atc_timezone', 'Australia/Sydney');
    $this->assertSession()->elementTextContains('css', 'div:contains(Date) + div .atc_privacy', 'public');
  }

}
