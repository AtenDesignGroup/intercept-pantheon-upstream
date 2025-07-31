<?php

namespace Drupal\Tests\duration_field\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class that provides some helper functions for functional tests.
 */
abstract class DurationFieldBrowserTestBase extends BrowserTestBase {

  /**
   * The granularity options of the duration field.
   *
   * @var array
   */
  const DURATION_GRANULARITY = [
    'y',
    'm',
    'd',
    'h',
    'i',
    's',
  ];

  /**
   * Admin user for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The custom content type created for testing.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $contentType;

  /**
   * Asserts that a status code is what it is supposed to be.
   */
  public function assertStatusCodeEquals($statusCode) {
    $this->assertSession()->statusCodeEquals($statusCode);
  }

  /**
   * Asserts an element exists on the page.
   */
  public function assertElementExists($selector) {
    $this->assertSession()->elementExists('css', $selector);
  }

  /**
   * Asserts that an attribute exists on an element.
   */
  public function assertElementAttributeExists($selector, $attribute) {
    $this->assertSession()->elementAttributeExists('css', $selector, $attribute);
  }

  /**
   * Asserts that an attribute on an element contains the given value.
   */
  public function assertElementAttributeContains($selector, $attribute, $value) {
    $this->assertSession()->elementAttributeContains('css', $selector, $attribute, $value);
  }

  /**
   * Selects a given radio element.
   */
  public function selectRadio($htmlID) {
    if (preg_match('/^#/', $htmlID)) {
      $htmlID = substr($htmlID, 1);
    }

    $radio = $this->getSession()->getPage()->findField($htmlID);
    $name = $radio->getAttribute('name');
    $option = $radio->getAttribute('value');
    $this->getSession()->getPage()->selectFieldOption($name, $option);
  }

  /**
   * Asserts that the value of a radio element was selected.
   */
  public function assertRadioSelected($htmlID) {
    if (!preg_match('/^#/', $htmlID)) {
      $htmlID = '#' . $htmlID;
    }

    $selected_radio = $this->getSession()->getPage()->find('css', 'input[type="radio"]:checked' . $htmlID);

    if (!$selected_radio) {
      throw new \Exception('Radio button with ID ' . $htmlID . ' is not selected');
    }
  }

  /**
   * Checks the given checkbox.
   */
  public function checkCheckbox($htmlID) {
    if (preg_match('/^#/', $htmlID)) {
      $htmlID = substr($htmlID, 1);
    }

    $this->getSession()->getPage()->checkField($htmlID);
  }

  /**
   * Asserts that a checkbox was checked.
   */
  public function assertCheckboxChecked($htmlID) {
    if (preg_match('/^#/', $htmlID)) {
      $htmlID = substr($htmlID, 1);
    }

    $this->assertSession()->checkboxChecked($htmlID);
  }

  /**
   * Fills in a value on a textfield.
   */
  public function fillTextValue($htmlID, $value) {
    if (preg_match('/^#/', $htmlID)) {
      $htmlID = substr($htmlID, 1);
    }

    $this->getSession()->getPage()->fillField($htmlID, $value);
  }

  /**
   * Asserts that the value submitted in a text field is correct.
   */
  public function assertTextValue($htmlID, $value) {
    if (preg_match('/^#/', $htmlID)) {
      $htmlID = substr($htmlID, 1);
    }

    $this->assertSession()->fieldValueEquals($htmlID, $value);
  }

  /**
   * Selects an option from a select element.
   */
  public function selectSelectOption($selectElementHtmlID, $value) {
    if (preg_match('/^#/', $selectElementHtmlID)) {
      $selectElementHtmlID = substr($selectElementHtmlID, 1);
    }

    $this->getSession()->getDriver()->selectOption(
      '//select[@id="' . $selectElementHtmlID . '"]',
      $value
    );
  }

  /**
   * Asserts that an element exists by it's xpath.
   */
  public function assertElementExistsXpath($selector) {
    $this->assertSession()->elementExists('xpath', $selector);
  }

  /**
   * Gets the HTML for a page.
   */
  public function getHtml() {
    $this->assertEquals('', $this->getSession()->getPage()->getHTML());
  }

  /**
   * Asserts that the given text exists on a page.
   */
  public function assertTextExists($text) {
    $this->assertSession()->pageTextContains($text);
  }

  /**
   * Asserts that the given text does not exist on the page.
   */
  public function assertTextNotExists($text) {
    $this->assertSession()->pageTextNotContains($text);
  }

  /**
   * Sets up a date.
   */
  protected function createDefaultSetup($granularity = self::DURATION_GRANULARITY, $include_weeks = FALSE) {
    $this->adminUser = $this->createUser([], 'Admin User', TRUE);
    $admin_role = $this->createAdminRole();
    $this->adminUser->addRole($admin_role);
    $this->drupalLogin($this->adminUser);
    $this->contentType = $this->createContentType(['type' => 'test_type', 'name' => 'Test Type']);
    $this->drupalGet('admin/structure/types/manage/test_type/fields/add-field');
    $this->assertStatusCodeEquals(200);

    if (version_compare(\Drupal::VERSION, '11.2.2', '<')) {
      $this->getSession()
        ->getPage()
        ->selectFieldOption('new_storage_type', 'duration');
      // Starting in 10.2, we have to click "Continue" to land on the form to
      // define the field label.
      if (version_compare(\Drupal::VERSION, '10.2', '>=')) {
        $this->click('#edit-submit');
      }
    }
    // 11.2.2 and higher can immediately click on the "Duration" link to pick
    // the field type.
    else {
      $this->getSession()->getPage()
        ->findLink('Duration')
        ->click();
    }

    $this->fillTextValue('#edit-label', 'Duration');
    $this->fillTextValue('#edit-field-name', 'duration');
    $this->click('#edit-submit');
    $this->assertStatusCodeEquals(200);

    // Before Drupal 10.2, we first land on the cardinality (storage) form, and
    // we need to click "Continue" again to land on the field settings form.
    if (version_compare(\Drupal::VERSION, '10.2', '<')) {
      $this->click('#edit-submit');
      $this->assertSession()->addressMatches('/^\/admin\/structure\/types\/manage\/test_type\/fields\/node.test_type.field_duration$/');
    }
    else {
      // /admin/structure/types/manage/test_type/add-field/node/field_duration
      $this->assertSession()->addressMatches('/^\/admin\/structure\/types\/manage\/test_type\/add-field\/node\/field_duration$/');
    }

    $check = array_diff(['y', 'm', 'd', 'h', 'i', 's'], $granularity);
    foreach ($check as $field) {
      $this->checkCheckbox('#edit-settings-granularity-' . $field);
    }

    if ($include_weeks) {
      $this->checkCheckbox('#edit-settings-include-weeks');
      $this->assertCheckboxChecked('#edit-settings-include-weeks');
    }

    foreach ($granularity as $field) {
      $this->assertCheckboxChecked('#edit-settings-granularity-' . $field);
    }
    $this->click('#edit-submit');
    $this->assertSession()->addressMatches('/^\/admin\/structure\/types\/manage\/test_type\/fields$/');
    $this->assertStatusCodeEquals(200);
    $this->assertElementExistsXpath('//table[@id="field-overview"]//td[text()="Duration"]');
    $this->drupalGet('node/add/test_type');
    $this->assertStatusCodeEquals(200);
    $this->assertSession()->addressMatches('/^\/node\/add\/test_type$/');
    foreach ($granularity as $field) {
      $this->assertElementExists('input#edit-field-duration-0-duration-' . $field . '[type="number"]');
    }
  }

  /**
   * Sets some human readable options.
   */
  protected function setHumanReadableOptions($text_length = 'full', $separator = 'space') {
    $this->drupalGet('/admin/structure/types/manage/test_type/display');
    $this->assertStatusCodeEquals(200);
    $this->click('#edit-fields-field-duration-settings-edit');
    $this->assertStatusCodeEquals(200);
    $this->selectSelectOption('#edit-fields-field-duration-settings-edit-form-settings-text-length', $text_length);
    $this->selectSelectOption('#edit-fields-field-duration-settings-edit-form-settings-separator', $separator);
    $this->click('#edit-fields-field-duration-settings-edit-form-actions-save-settings');
    $this->assertStatusCodeEquals(200);
    $this->click('#edit-submit');
    $this->assertStatusCodeEquals(200);
  }

  /**
   * Sets the formatter to be tested.
   */
  protected function setFormatter($formatter) {
    $types = [
      'raw' => 'duration_string_display',
      'human' => 'duration_human_display',
      'time' => 'duration_time_display',
    ];

    $this->drupalGet('/admin/structure/types/manage/test_type/display');
    $this->assertStatusCodeEquals(200);
    $this->selectSelectOption('#edit-fields-field-duration-type', $types[$formatter]);
    $this->click('#edit-submit');
  }

}
