<?php

namespace Drupal\Tests\duration_field\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the JavaScript #states functionality of 'duration' form elements.
 *
 * @group duration_field
 */
class DurationElementStatesTest extends WebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'duration_field',
    'duration_field_form_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Regular authenticated User for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->testUser = $this->createUser();
    $this->drupalLogin($this->testUser);
  }

  /**
   * Tests JavaScript #states functionality for 'duration' elements.
   */
  public function testDurationStates() {
    $this->drupalGet('/duration-field-form-test/duration-element-states');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Test states of elements triggered by a checkbox element.
    $trigger = $page->findField('checkbox_trigger');
    $this->assertNotEmpty($trigger);

    // Check initial state.
    $duration_invisible_element = $assert_session->elementExists('css', 'form div.js-form-item-duration-invisible-when-checkbox-trigger-checked');
    $duration_invisible_element_label = $assert_session->elementExists('css', 'form div.js-form-item-duration-invisible-when-checkbox-trigger-checked label');

    $duration_invisible_element_days = $page->findField('duration_invisible_when_checkbox_trigger_checked[d]');
    $duration_invisible_element_hours = $page->findField('duration_invisible_when_checkbox_trigger_checked[h]');
    $duration_invisible_element_seconds = $page->findField('duration_invisible_when_checkbox_trigger_checked[s]');
    $this->assertTrue($duration_invisible_element->isVisible());
    $this->assertTrue($duration_invisible_element_label->isVisible());
    $this->assertTrue($duration_invisible_element_days->isVisible());
    $this->assertTrue($duration_invisible_element_hours->isVisible());
    $this->assertTrue($duration_invisible_element_seconds->isVisible());

    // Change state: check the checkbox.
    $trigger->check();

    // Test that the duration and sub-elements are not visible anymore.
    $this->assertFalse($duration_invisible_element->isVisible());
    $this->assertFalse($duration_invisible_element_label->isVisible());
    $this->assertFalse($duration_invisible_element_days->isVisible());
    $this->assertFalse($duration_invisible_element_hours->isVisible());
    $this->assertFalse($duration_invisible_element_seconds->isVisible());

  }

}
