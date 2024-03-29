<?php

declare(strict_types = 1);

namespace Drupal\Tests\webformautosave\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that pages load with module enabled.
 *
 * @group webformautosave
 */
class LoadTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'webform',
    'webform_submission_log',
    'webformautosave',
  ];

  /**
   * Tests that the admin page loads.
   */
  public function testAdmin(): void {
    $admin_user = $this->drupalCreateUser(['access administration pages'], NULL, TRUE);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Administration');
  }

}
