<?php

declare(strict_types = 1);

namespace Drupal\Tests\webformautosave\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webformautosave third party settings.
 *
 * @group webformautosave
 */
class WebformAutosaveThirdPartySettingsTest extends WebformBrowserTestBase {

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
   * Tests webform third party settings.
   */
  public function testThirdPartySettings() {
    // Get Webform storage.
    $webform_storage = \Drupal::entityTypeManager()->getStorage('webform');

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $webform_storage->load('contact');

    $this->drupalLogin($this->rootUser);

    // Check webformautosave (custom) third party setting does not exist.
    $this->assertNull($webform->getThirdPartySetting('webformautosave', 'auto_save'));

    // Add webformautosave (custom) third party setting.
    $webform = $this->reloadWebform('contact');
    $webform->setThirdPartySetting('webformautosave', 'auto_save', TRUE);
    $webform->save();

    // Check webformautosave (custom) third party setting.
    $webform = $this->reloadWebform('contact');
    $this->assertTrue($webform->getThirdPartySetting('webformautosave', 'auto_save'));

    // Check 'Contact: Settings' shows 'Third party settings'.
    $this->drupalGet('/admin/structure/webform/manage/contact/settings');
    $this->assertSession()->responseContains('Third party settings');

    // Check 'Contact: Settings: Third party' auto save.
    $this->submitForm([], 'Save');

    // Check webformautosave (custom) third party setting.
    $webform = $this->reloadWebform('contact');
    $this->assertTrue($webform->getThirdPartySetting('webformautosave', 'auto_save'));
    $this->assertEquals(5000, $webform->getThirdPartySetting('webformautosave', 'auto_save_time'));

    // Check 'Contact: Settings: Third party' auto save.
    $edit = [
      'third_party_settings[webformautosave][auto_save_time]' => 10000,
    ];
    $this->submitForm($edit, 'Save');

    // Check webformautosave (custom) third party setting.
    $webform = $this->reloadWebform('contact');
    $this->assertTrue($webform->getThirdPartySetting('webformautosave', 'auto_save'));
    $this->assertEquals(10000, $webform->getThirdPartySetting('webformautosave', 'auto_save_time'));
  }

}
